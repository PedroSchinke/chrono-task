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

const props = defineProps(['tarefa', 'dias', 'tamanhoDia', 'maquina', 'horariosDisponiveis']);
const emit = defineEmits(['reposicionar', 'recarregar']);

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
const dragStartX = ref(0);
const dragStartY = ref(0);
const offsetX = ref(0);
const offsetY = ref(0);

const diasEntre = (inicio, fim) => {
    return dayjs(fim).startOf('day').diff(dayjs(inicio).startOf('day'), 'day');
}

const blocoStyle = computed(() => {
    const inicioEmDias = diasEntre(props.dias[0], props.tarefa.inicio) + fracaoDoDia(props.tarefa.inicio);
    const fimEmDias = diasEntre(props.dias[0], props.tarefa.fim) + fracaoDoDia(props.tarefa.fim);
    let duracao = fimEmDias - inicioEmDias;

    let left = inicioEmDias * props.tamanhoDia;
    let top = 0;

    if (dragging.value) {
        left += offsetX.value;
        top = offsetY.value;
    }

    if (resizing.value) {
        const diff = resizeDiffDias.value;

        if (resizeDirection.value === 'start') {
            left += diff * props.tamanhoDia;
            duracao -= diff;
        } else if (resizeDirection.value === 'end') {
            duracao += diff;
        }
    }

    if (left < 0) left = 0;

    return {
        left: `${left}px`,
        top: `${top}px`,
        width: `${duracao * props.tamanhoDia}px`,
        backgroundColor: props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor,
        cursor: 'grab',
        position: 'absolute',
    };
});

function fracaoDoDia(data) {
    const date = new Date(data);
    const horas = date.getHours();
    const minutos = date.getMinutes();
    return (horas + minutos / 60) / 24; // valor entre 0 e 1
}

const resizeStartX = ref(0);

const resizing = ref(false);
const resizeDirection = ref(null);
const resizeDiffDias = ref(0);

const startResize = (mode, event) => {
    event.preventDefault();

    resizing.value = true;
    resizeDirection.value = mode;
    resizeStartX.value = event.clientX;
    resizeDiffDias.value = 0;

    blocoFoiArrastado.value = false;

    document.addEventListener('mousemove', onResize);
    document.addEventListener('mouseup', stopResize);
};

const onResize = (event) => {
    const diffPx = event.clientX - resizeStartX.value;
    const diffDias = Math.round(diffPx / props.tamanhoDia);

    if (diffPx > 5 || diffPx < -5) {
        blocoFoiArrastado.value = true;
    }

    resizeDiffDias.value = diffDias;
};

const stopResize = async () => {
    document.removeEventListener('mousemove', onResize);
    document.removeEventListener('mouseup', stopResize);

    resizing.value = false;
    const diff = resizeDiffDias.value;
    const direction = resizeDirection.value;

    resizeDirection.value = null;
    resizeDiffDias.value = 0;

    if (diff === 0) return;

    const novaTarefa = { ...props.tarefa };

    if (direction === 'start') {
        novaTarefa.inicio = dayjs(props.tarefa.inicio).add(diff, 'day').format('YYYY-MM-DD');
    } else if (direction === 'end') {
        novaTarefa.fim = dayjs(props.tarefa.fim).add(diff, 'day').format('YYYY-MM-DD');
    }

    try {
        const diasReposicionamento = getDates(dayjs(novaTarefa.inicio), dayjs(novaTarefa.fim));

        diasReposicionamento.forEach((dia) => {
            const diaSemana = getDiaSemana(dia.day()).name;

            const disponivel = props.maquina.horarios_disponiveis.some((horario) => {
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

        loading.value = true;

        const params = {
            inicio: novaTarefa.inicio,
            fim: novaTarefa.fim,
            id_maquina: props.tarefa.id_maquina,
        };

        await api.post(`/tarefa/${props.tarefa.id}/reposicionar`, params);

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
    dragging.value = true;
    blocoFoiArrastado.value = false;
    dragStartX.value = event.clientX;
    dragStartY.value = event.clientY;

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
}

const onDrag = (event) => {
    const deslocamento = Math.abs(event.clientX - dragStartX.value);

    if (deslocamento > 5) {
        blocoFoiArrastado.value = true;
    }

    offsetX.value = event.clientX - dragStartX.value;
    offsetY.value = event.clientY - dragStartY.value;
}

const stopDrag = () => {
    dragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);

    const deslocamento = offsetX.value / props.tamanhoDia;

    const deslocamentoInteiros = deslocamento > 0
        ? Math.floor(deslocamento)
        : Math.ceil(deslocamento);

    const deslocamentoY = offsetY.value;
    let deslocamentoMaquina = Math.trunc(offsetY.value / 60);

    if (deslocamentoInteiros !== 0 || deslocamento % 1 > 0.8 || deslocamento % 1 < -0.8 || deslocamentoMaquina !== 0) {
        let deslocamentoDias = deslocamentoInteiros;

        if (deslocamento % 1 > 0.8) {
            deslocamentoDias++;
        }

        if (deslocamento % 1 < -0.8) {
            deslocamentoDias--;
        }

        emit('reposicionar', { tarefa: props.tarefa, deslocamento: deslocamentoDias, posY: deslocamentoY });
    }

    offsetX.value = 0;
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

        await api.post(`/tarefa/${props.tarefa.id}`, params);
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
    popoverForm.titulo = props.tarefa.titulo;
    popoverForm.descricao = props.tarefa.descricao;
    popoverForm.maquina = { id: props.tarefa.id_maquina, nome: props.maquina.nome };
    popoverForm.inicio = new Date(props.tarefa.inicio);
    popoverForm.fim = new Date(props.tarefa.fim);
    popoverForm.periodo_diario_inicio = props.tarefa.periodo_diario_inicio.slice(0, 5);
    popoverForm.periodo_diario_fim = props.tarefa.periodo_diario_fim.slice(0, 5);
    popoverForm.cor = props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor;
}
</script>

<template>
    <Toast />

    <ModalLoading :is-loading="loading" message="Salvando alterações..." />

    <div
        :id="`tarefa-${tarefa.id}`"
        :style="blocoStyle"
        class="tarefa-bloco"
        @click="toggle"
    >
        <div class="resize-handle left" @mousedown="startResize('start', $event)"></div>

        <div class="bloco-texto" @mousedown="startDrag">{{ props.tarefa.titulo }}</div>

        <div class="resize-handle right" @mousedown="startResize('end', $event)"></div>
    </div>

    <Popover ref="tarefaPopover" @hide="resetarDados()">
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
    white-space: break-spaces;
    text-align: center;
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
