<script setup>
import { ref, computed, reactive } from 'vue';
import { useToast } from "primevue/usetoast";
import { getDates } from "@/helpers/getDates.js";
import { getDiaSemana } from "@/helpers/getDiaSemana.js";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";
import dayjs from "dayjs";
import Popover from 'primevue/popover';
import Button from 'primevue/button';
import ColorPicker from 'primevue/colorpicker';
import Toast from "primevue/toast";
import api from "@/axios.js";
import ModalLoading from "@/components/ModalLoading.vue";
import DatePicker from "primevue/datepicker";
import InputText from "primevue/inputtext";
import AutoComplete from "primevue/autocomplete";

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
const emit = defineEmits(['reposicionar', 'recarregar']);

const tarefaLocal = computed(() => {
    return { ...props.tarefa };
});

const loading = ref(false);

const maquinas = ref([]);
const loadingMaquinas = ref(false);

const popoverForm = reactive({
    titulo: props.tarefa.titulo,
    descricao: props.tarefa.descricao,
    maquina: {
        id: props.tarefa.id_maquina,
        nome: props.maquina.nome
    },
    inicio: new Date(props.tarefa.inicio),
    fim: new Date(props.tarefa.fim),
    periodo_diario_inicio: props.tarefa.periodo_diario_inicio.slice(0, 5),
    periodo_diario_fim: props.tarefa.periodo_diario_fim.slice(0, 5),
    cor: props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor
});

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

        loading.value = true;

        const params = {
            inicio: tarefaLocal.value.inicio,
            fim: tarefaLocal.value.fim,
            id_maquina: tarefaLocal.value.id_maquina,
        };

        await api.post(`/tarefa/${tarefaLocal.value.id}/reposicionar`, params);

        emit('recarregar');
    } catch (e) {
        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }
    } finally {
        loading.value = false;
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

const salvarAlteracoesTarefa = async () => {
    loading.value = true;

    try {
        validarHorarios();

        const params = {
            titulo: popoverForm.titulo,
            descricao: popoverForm.descricao,
            inicio: popoverForm.inicio,
            fim: popoverForm.fim,
            id_maquina: popoverForm.maquina.id,
            periodo_diario_inicio: popoverForm.periodo_diario_inicio,
            periodo_diario_fim: popoverForm.periodo_diario_fim,
            cor: popoverForm.cor
        }

        await api.post(`/tarefa/${tarefaLocal.value.id}`, params);
    } catch (e) {
        loading.value = false;

        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }

        return;
    }

    loading.value = false;

    toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa editada com sucesso', life: 3000 });

    emit('recarregar');
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

const getMaquinas = async () => {
    loadingMaquinas.value = true;

    try {
        const resp = await api.get(`/maquinas`);

        maquinas.value = resp.data.data;

        loadingMaquinas.value = false;
    } catch (e) {
        loadingMaquinas.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível pesquisar máquinas', life: 3000 });
    }
}

const toggle = (event) => {
    if (!blocoFoiArrastado.value) {
        tarefaPopover.value.toggle(event);
    }
}

const resetarDados = () => {
    popoverForm.titulo = tarefaLocal.value.titulo;
    popoverForm.descricao = tarefaLocal.value.descricao;
    popoverForm.maquina = { id: tarefaLocal.value.id_maquina, nome: props.maquina.nome };
    popoverForm.inicio = new Date(tarefaLocal.value.inicio);
    popoverForm.fim = new Date(tarefaLocal.value.fim);
    popoverForm.periodo_diario_inicio = tarefaLocal.value.periodo_diario_inicio.slice(0, 5);
    popoverForm.periodo_diario_fim = tarefaLocal.value.periodo_diario_fim.slice(0, 5);
    popoverForm.cor = tarefaLocal.value.cor.startsWith('#') ? tarefaLocal.value.cor : '#' + tarefaLocal.value.cor;
}
</script>

<template>
    <Toast />

    <ModalLoading :is-loading="loading" message="Salvando alterações..." />

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

    <Popover ref="tarefaPopover" @hide="resetarDados()" @show="resetarDados()">
        <div class="popover-tarefa-infos">
            <div class="popover-info">
                <span style="font-weight: bold;">Título</span>

                <InputText placeholder="Título" v-model="popoverForm.titulo"/>
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Descrição</span>

                <InputText placeholder="Descrição" v-model="popoverForm.descricao"/>
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Máquina</span>

                <AutoComplete
                    placeholder="Máquina"
                    v-model="popoverForm.maquina"
                    :loading="loadingMaquinas"
                    :suggestions="maquinas"
                    empty-search-message="Não foram encontradas máquinas"
                    option-label="nome"
                    dropdown
                    force-selection
                    @complete="getMaquinas($event)"
                />
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Início</span>

                <DatePicker
                    placeholder="Início da tarefa"
                    v-model="popoverForm.inicio"
                    :manualInput="false"
                    :step-minute="30"
                    date-format="dd/mm/yy"
                    show-icon
                    show-button-bar
                    show-time
                    @clear-click="popoverForm.inicio = ''"
                />
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Fim</span>

                <DatePicker
                    placeholder="Fim da tarefa"
                    v-model="popoverForm.fim"
                    :manualInput="false"
                    :step-minute="30"
                    date-format="dd/mm/yy"
                    show-icon
                    show-button-bar
                    show-time
                    @clear-click="popoverForm.fim = ''"
                />
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Período de atividade diário</span>

                <div class="hora-inputs-container">
                    <DatePicker
                        v-model="popoverForm.periodo_diario_inicio"
                        input-id="periodo-atividade-inicio-input"
                        time-only
                        fluid
                        :step-minute="5"
                        style="width: 70px;"
                    />

                    <label class="label" for="periodo-atividade-fim-input">Até</label>

                    <DatePicker
                        v-model="popoverForm.periodo_diario_fim"
                        input-id="periodo-atividade-fim-input"
                        time-only
                        fluid
                        :step-minute="5"
                        style="width: 70px;"
                    />
                </div>
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Cor</span>

                <ColorPicker v-model="popoverForm.cor" />
            </div>
        </div>

        <div class="button-container">
            <Button label="Salvar" rounded style="margin-top: 10px;" @click="salvarAlteracoesTarefa()" />
        </div>
    </Popover>
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

.popover-tarefa-infos {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.popover-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.hora-inputs-container {
    display: flex;
    align-items: center;
    gap: 5px;
}

.button-container {
    width: 100%;
    display: flex;
    justify-content: center;
}
</style>
