<script setup>
import { ref, computed, reactive } from 'vue';
import { useTarefasStore } from "@/stores/tarefas.js";
import { useLoadingStore } from "@/stores/loading.js";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { getDates } from "@/helpers/getDates.js";
import { getDiaSemana } from "@/helpers/getDiaSemana.js";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";
import dayjs from "dayjs";
import api from "@/axios.js";
import TarefaPopover from "@/pages/home/components/TarefaPopover.vue";

const toast = useToast();
const confirm = useConfirm();

const props = defineProps([
    'tarefa',
    'dias',
    'larguraDiaPx',
    'alturaTarefaPx',
    'horasExibidas',
    'maquina',
    'horariosDisponiveis'
]);
const emit = defineEmits(['reposicionar']);

const tarefaLocal = computed(() => {
    return { ...props.tarefa };
});

const tarefasStore = useTarefasStore();

const loading = useLoadingStore();

const tarefaPopover = ref();

const blocoFoiArrastado = ref(false);

const dragging = ref(false);
const dragDiffX = ref(0);
const dragDiffY = ref(0);
const dragStartX = ref(0);
const dragStartY = ref(0);

const resizing = ref(false);
const resizeStartX = ref(0);
const resizeDirection = ref(null);
const resizeDiff = ref(0);

const diasEntre = (inicio, fim) => {
    return dayjs(fim).startOf('day').diff(dayjs(inicio).startOf('day'), 'day');
}

const blocoStyle = computed(() => {
    const inicio = diasEntre(props.dias[0], tarefaLocal.value.inicio) + fracaoDoDia(tarefaLocal.value.inicio);
    const fim = diasEntre(props.dias[0], tarefaLocal.value.fim) + fracaoDoDia(tarefaLocal.value.fim);
    let duracao = fim - inicio;

    let leftPx = inicio * props.larguraDiaPx;
    let widthPx = duracao * props.larguraDiaPx;
    let top = dragDiffY.value * props.alturaTarefaPx;

    if (dragging.value) {
        leftPx += dragDiffX.value * divisions.value.divisionPx;
        top = dragDiffY.value * props.alturaTarefaPx;
    }

    if (resizing.value) {
        const deltaPx = resizeDiff.value * divisions.value.divisionPx;

        if (resizeDirection.value === 'start') {
            leftPx += deltaPx;
            widthPx -= deltaPx;
        } else {
            widthPx += deltaPx;
        }
    }

    return {
        left: `${Math.max(0, leftPx)}px`,
        top: `${top}px`,
        width: `${widthPx}px`,
        backgroundColor: props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor,
        cursor: 'grab',
        position: 'absolute',
    }
});

const divisions = computed(() => {
    const divisores = props.horasExibidas.length;
    const divisionPx = props.larguraDiaPx / divisores;
    const divisionHours = 24 / divisores;
    const divisionMinutes = divisionHours * 60;

    return { divisores, divisionPx, divisionHours, divisionMinutes }
});

function fracaoDoDia(data) {
    const date = new Date(data);
    const horas = date.getHours();
    const minutos = date.getMinutes();

    return (horas + minutos / 60) / 24;
}

const startResize = (mode, event) => {
    event.preventDefault();

    resizing.value = true;
    resizeDirection.value = mode;
    resizeStartX.value = event.clientX;
    resizeDiff.value = 0;

    blocoFoiArrastado.value = false;

    document.addEventListener('mousemove', onResize);
    document.addEventListener('mouseup', stopResize);
}

const onResize = (event) => {
    const diffPx = event.clientX - resizeStartX.value;
    const diff = Math.round(diffPx / divisions.value.divisionPx);

    if (diffPx > 5 || diffPx < -5) {
        blocoFoiArrastado.value = true;
    }

    resizeDiff.value = diff;
}

const stopResize = async () => {
    document.removeEventListener('mousemove', onResize);
    document.removeEventListener('mouseup', stopResize);

    resizing.value = false;

    if (resizeDiff.value === 0) return;

    const minutesToAdd = resizeDiff.value * divisions.value.divisionMinutes;

    let novoInicio = tarefaLocal.value.inicio;
    let novoFim = tarefaLocal.value.fim;

    if (resizeDirection.value === 'start') {
        novoInicio = dayjs(tarefaLocal.value.inicio).add(minutesToAdd, 'minute').format('YYYY-MM-DD HH:mm:ss');
    } else {
        novoFim = dayjs(tarefaLocal.value.fim).add(minutesToAdd, 'minute').format('YYYY-MM-DD HH:mm:ss');
    }

    try {
        // const diasReposicionamento = getDates(dayjs(novoInicio), dayjs(novoFim));
        //
        // diasReposicionamento.forEach((dia) => {
        //     const diaSemana = getDiaSemana(dia.day()).name;
        //
        //     const disponivel = props.maquina.horarios_disponiveis.some((horario) => {
        //         return diaSemana === horario.dia_semana;
        //     });
        //
        //     if (!disponivel) {
        //         throw new HorarioIndisponivelError(
        //             'Não foi possível salvar alterações da tarefa',
        //             'Horário indisponível para a máquina'
        //         );
        //     }
        // });

        if (resizeDirection.value === 'start') {
            tarefaLocal.value.inicio = novoInicio;
        } else if (resizeDirection.value === 'end') {
            tarefaLocal.value.fim = novoFim;
        }

        loading.show('Reposicionando Tarefa...');

        const params = {
            inicio: tarefaLocal.value.inicio,
            fim: tarefaLocal.value.fim,
            id_maquina: tarefaLocal.value.id_maquina,
        };

        await api.post(`/tarefa/${tarefaLocal.value.id}/reposicionar`, params);

        loading.hide();

        tarefasStore.getTarefas();
    } catch (e) {
        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }
    } finally {
        loading.hide();
    }
}

const startDrag = (event) => {
    event.preventDefault();

    dragging.value = true;
    blocoFoiArrastado.value = false;
    dragStartX.value = event.clientX;
    dragStartY.value = event.clientY;
    dragDiffX.value = 0;
    dragDiffY.value = 0;

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
}

const onDrag = (event) => {
    const deslocamentoXPx = event.clientX - dragStartX.value;
    const deslocamentoYPx = event.clientY - dragStartY.value;

    if (deslocamentoXPx > 5 || deslocamentoXPx < -5 || deslocamentoYPx > 5 || deslocamentoYPx < -5) {
        blocoFoiArrastado.value = true;
    }

    const deslocamentoX = Math.round(deslocamentoXPx / divisions.value.divisionPx);
    const deslocamentoY = Math.round(deslocamentoYPx / divisions.value.divisionPx);

    dragDiffX.value = deslocamentoX;
    dragDiffY.value = deslocamentoY;
}

const stopDrag = () => {
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);

    dragging.value = false;

    const deslocamentoX = dragDiffX.value * divisions.value.divisionMinutes;
    const deslocamentoY = dragDiffY.value;

    if (deslocamentoX === 0 && deslocamentoY === 0) return;

    tarefaLocal.value.inicio = dayjs(tarefaLocal.value.inicio).add(deslocamentoX, 'minute').format('YYYY-MM-DD HH:mm:ss');
    tarefaLocal.value.fim = dayjs(tarefaLocal.value.fim).add(deslocamentoX, 'minute').format('YYYY-MM-DD HH:mm:ss');

    emit('reposicionar', { tarefa: props.tarefa, deslocamentoX, deslocamentoY });
}

const validarHorarios = () => {
    const horariosDisponiveisDaMaquina = props.horariosDisponiveis.filter((horario) => {
        return horario.id_maquina == popoverForm.maquina.id;
    });

    const diasReposicionamento = getDates(dayjs(popoverForm.inicio), dayjs(popoverForm.fim));

    diasReposicionamento.forEach((dia) => {
        const diaSemana = getDiaSemana(dia.day()).name;

        const disponivel = horariosDisponiveisDaMaquina.some((horario) => {
            return diaSemana === horario.dia_semana &&
                popoverForm.periodo_diario_inicio >= horario.hora_inicio.slice(0, 5) &&
                popoverForm.periodo_diario_fim <= horario.hora_fim.slice(0, 5);
        });

        if (!disponivel) {
            throw new HorarioIndisponivelError(
                'Não foi possível salvar alterações da tarefa',
                'Horário indisponível para a máquina'
            );
        }
    });
}

const toggle = (event) => {
    if (!blocoFoiArrastado.value) {
        tarefaPopover.value.toggle(event);
    }
}
</script>

<template>
    <TarefaPopover ref="tarefaPopover" :tarefa="props.tarefa" />

    <div
        :id="`tarefa-${tarefaLocal.id}`"
        :key="tarefaLocal.id"
        :style="blocoStyle"
        class="tarefa-bloco"
        @click="toggle"
    >
        <div class="resize-handle left" @mousedown="startResize('start', $event)"></div>

        <div class="bloco-texto" @mousedown="startDrag">{{ props.tarefa.titulo }}</div>

        <div class="resize-handle right" @mousedown="startResize('end', $event)"></div>
    </div>
</template>

<style scoped>
.tarefa-bloco {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border-radius: 4px;
    user-select: none;
    z-index: 10;
}

.tarefa-bloco {
    position: absolute;
    cursor: grab;
}

.resize-handle {
    position: absolute;
    width: 5px;
    top: 0;
    bottom: 0;
    z-index: 10;
}

.resize-handle.left {
    left: 0;
    cursor: w-resize;
}

.resize-handle.right {
    right: 0;
    cursor: e-resize;
}

.bloco-texto {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    text-align: center;
    white-space: break-spaces;
}

:deep(.p-chip) {
    width: fit-content;
}
</style>
