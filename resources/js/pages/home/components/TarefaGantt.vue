<script setup>
import { ref, computed } from 'vue';
import { useTarefasStore } from "@/stores/tarefas.js";
import { useLoadingStore } from "@/stores/loading.js";
import { useToast } from "primevue/usetoast";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";
import {
    validarDisponibilidadeColaboradores,
    validarDisponibilidadeMaquinas
} from "@/helpers/validarDisponibilidade.js";
import dayjs from "dayjs";
import api from "@/axios.js";
import TarefaPopover from "@/pages/home/components/TarefaPopover.vue";

const toast = useToast();

const props = defineProps([
    'tarefa',
    'dias',
    'larguraDiaPx',
    'alturaTarefaPx',
    'horasExibidas',
    'maquina',
    'horariosDisponiveis'
]);

const tarefaLocal = ref({ ...props.tarefa });

const tarefasStore = useTarefasStore();

const loading = useLoadingStore();

const tarefaPopover = ref(null);

const blocoFoiArrastado = ref(false);

const dragging = ref(false);
const dragDiffX = ref(0);
const dragStartX = ref(0);

const resizing = ref(false);
const resizeStartX = ref(0);
const resizeDirection = ref(null);
const resizeDiff = ref(0);

const cor = ref(props.tarefa.cor);

const diasEntre = (inicio, fim) => {
    return dayjs(fim).startOf('day').diff(dayjs(inicio).startOf('day'), 'day');
}

const blocoStyle = computed(() => {
    const inicio = diasEntre(props.dias[0], tarefaLocal.value.inicio) + fracaoDoDia(tarefaLocal.value.inicio);
    const fim = diasEntre(props.dias[0], tarefaLocal.value.fim) + fracaoDoDia(tarefaLocal.value.fim);
    let duracao = fim - inicio;

    let leftPx = inicio * props.larguraDiaPx;
    let widthPx = duracao * props.larguraDiaPx;

    if (dragging.value) {
        leftPx += dragDiffX.value * divisions.value.divisionPx;
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
        backgroundColor: cor.value.startsWith('#') ? cor.value : '#' + cor.value,
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

const stopResize = () => {
    document.removeEventListener('mousemove', onResize);
    document.removeEventListener('mouseup', stopResize);

    resizing.value = false;

    if (resizeDiff.value === 0) return;

    const minutesToAdd = resizeDiff.value * divisions.value.divisionMinutes;

    let novoInicio = dayjs(props.tarefa.inicio);
    let novoFim = dayjs(props.tarefa.fim);

    if (resizeDirection.value === 'start') {
        novoInicio = dayjs(props.tarefa.inicio).add(minutesToAdd, 'minute');
    } else {
        novoFim = dayjs(props.tarefa.fim).add(minutesToAdd, 'minute');
    }

    reposicionarTarefa(novoInicio, novoFim);
}

const startDrag = (event) => {
    event.preventDefault();

    dragging.value = true;
    blocoFoiArrastado.value = false;
    dragStartX.value = event.clientX;
    dragDiffX.value = 0;

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
}

const onDrag = (event) => {
    const deslocamentoXPx = event.clientX - dragStartX.value;

    if (deslocamentoXPx > 5 || deslocamentoXPx < -5) {
        blocoFoiArrastado.value = true;
    }

    const deslocamentoX = Math.round(deslocamentoXPx / divisions.value.divisionPx);

    dragDiffX.value = deslocamentoX;
}

const stopDrag = () => {
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);

    dragging.value = false;

    const deslocamentoX = dragDiffX.value * divisions.value.divisionMinutes;

    if (deslocamentoX === 0) return;

    const novaDataInicio = dayjs(props.tarefa.inicio).add(deslocamentoX, 'minute');
    const novaDataFim = dayjs(props.tarefa.fim).add(deslocamentoX, 'minute');

    reposicionarTarefa(novaDataInicio, novaDataFim);
}

const reposicionarTarefa = async (dataInicio, dataFim) => {
    try {
        validarDisponibilidade(dataInicio, dataFim);

        tarefaLocal.value.inicio = dataInicio;
        tarefaLocal.value.fim = dataFim;

        loading.show('Reposicionando Tarefa...');

        await api.post(`/tarefa/${props.tarefa.id}/reposicionar`, {
            inicio: dataInicio.format('YYYY-MM-DD HH:mm:ss'),
            fim: dataFim.format('YYYY-MM-DD HH:mm:ss')
        });

        loading.hide();

        tarefasStore.getTarefas();
    } catch (e) {
        loading.hide();

        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 5000 });
        } else if (e.status !== 500) {
            let message = e.message;

            if (e.response) {
                message = e.response.data.message;
            }

            toast.add({ severity: 'error', summary: 'Erro', detail: message, life: 5000 });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Algo deu errado...',
                detail: `Não foi possível reposicionar ${props.tarefa.titulo}`,
                life: 3000
            });
        }

        tarefaLocal.value.inicio = props.tarefa.inicio;
        tarefaLocal.value.fim = props.tarefa.fim;
    }
}

const validarDisponibilidade = (inicio, fim) => {
    validarDisponibilidadeColaboradores(props.tarefa.colaboradores, inicio, fim, props.tarefa.id);
    validarDisponibilidadeMaquinas(props.tarefa.maquinas, inicio, fim, props.tarefa.id);
}

const toggle = (event) => {
    if (!blocoFoiArrastado.value) {
        tarefaPopover.value.toggle(event);
    }
}
</script>

<template>
    <TarefaPopover ref="tarefaPopover" :tarefa="props.tarefa" @change-color="(value) => cor = value" />

    <div
        ref="blocoTarefa"
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
</style>
