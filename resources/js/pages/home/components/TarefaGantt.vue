<script setup>
import { ref, computed, reactive } from 'vue';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { getDates } from "@/helpers/getDates.js";
import { getDiaSemana } from "@/helpers/getDiaSemana.js";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";
import { Form } from '@primevue/forms';
import dayjs from "dayjs";
import api from "@/axios.js";
import IftaLabel from "primevue/iftalabel";
import InputText from "primevue/inputtext";
import Popover from 'primevue/popover';
import Button from 'primevue/button';
import ColorPicker from 'primevue/colorpicker';
import DatePicker from "primevue/datepicker";
import Chip from 'primevue/chip';
import ModalLoading from "@/components/ModalLoading.vue";
import Message from "primevue/message";

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
const emit = defineEmits(['reposicionar', 'recarregar']);

const tarefaLocal = computed(() => {
    return { ...props.tarefa };
});

const loading = ref(false);
const loadingMessage = ref('Carregando...');

const popoverForm = reactive({
    titulo: props.tarefa.titulo,
    descricao: props.tarefa.descricao,
    maquina: [],
    inicio: new Date(props.tarefa.inicio),
    fim: new Date(props.tarefa.fim),
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

const resolver = ({ values }) => {
    const errors = {};

    if (!values.titulo) {
        errors.titulo = [{ message: 'Título é obrigatório.' }];
    }

    if (!values.inicio) {
        errors.inicio = [{ message: 'Início é obrigatório.' }];
    }

    if (!values.fim) {
        errors.fim = [{ message: 'Fim é obrigatório.' }];
    }

    if (values.inicio > values.fim) {
        errors.fim = [{ message: 'Data do fim precisa ser posterior à data de início.' }];
    }

    return { values, errors };
}

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
    loadingMessage.value = 'Salvando Alterações...';
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

const confirmarExclusaoTarefa = async () => {
    confirm.require({
        header: 'Confirmação',
        message: `Tem certeza que deseja excluir a tarefa "${props.tarefa.titulo}"?`,
        rejectProps: {
            label: 'Não, cancelar',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Sim, excluir'
        },
        accept: () => {
            excluirTarefa();
        }
    });
}

const excluirTarefa = async () => {
    loadingMessage.value = 'Excluindo Tarefa...';
    loading.value = true;

    try {
        await api.post(`/tarefa/deletar/${props.tarefa.id}`);

        loading.value = false;

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa excluída com sucesso', life: 3000 });
    } catch (e) {
        loading.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: e.response.data.message, life: 3000 });
    }
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

const resetarDados = () => {
    popoverForm.titulo = tarefaLocal.value.titulo;
    popoverForm.descricao = tarefaLocal.value.descricao;
    popoverForm.maquina = [];
    popoverForm.inicio = new Date(tarefaLocal.value.inicio);
    popoverForm.fim = new Date(tarefaLocal.value.fim);
    popoverForm.cor = tarefaLocal.value.cor.startsWith('#') ? tarefaLocal.value.cor : '#' + tarefaLocal.value.cor;
}
</script>

<template>
    <ModalLoading :is-loading="loading" :message="loadingMessage" />

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
        <Form
            v-slot="$form"
            :initial-values="popoverForm"
            :resolver
            class="popover-form"
            @submit="salvarAlteracoesTarefa"
        >
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <IftaLabel style="width: 100%;">
                        <label for="titulo">Título</label>

                        <InputText v-model="popoverForm.titulo" id="titulo" name="titulo" fluid />
                    </IftaLabel>

                    <ColorPicker
                        v-model="popoverForm.cor"
                        title="Selecionar Cor"
                        input-id="cor"
                        name="cor"
                        format="hex"
                    />
                </div>

                <Message
                    v-if="$form.titulo?.invalid"
                    severity="error"
                    size="small"
                    variant="simple"
                    class="input-message"
                    style="margin-top: 3px;"
                >
                    {{ $form.titulo.error?.message }}
                </Message>
            </div>

            <div class="popover-info">
                <IftaLabel style="width: 100%;">
                    <label for="descricao">Descrição</label>

                    <InputText v-model="popoverForm.descricao" id="descricao" name="descricao" fluid />
                </IftaLabel>
            </div>

            <div class="popover-info">
                <IftaLabel style="width: 100%;">
                    <label for="inicio">Início</label>

                    <DatePicker
                        v-model="popoverForm.inicio"
                        placeholder="Início da tarefa"
                        :manualInput="false"
                        :step-minute="5"
                        input-id="inicio"
                        name="inicio"
                        date-format="dd/mm/yy"
                        show-icon
                        show-time
                    />
                </IftaLabel>

                <Message v-if="$form.inicio?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.inicio.error?.message }}
                </Message>
            </div>

            <div class="popover-info">
                <IftaLabel style="width: 100%;">
                    <label for="fim">Fim</label>

                    <DatePicker
                        v-model="popoverForm.fim"
                        placeholder="Início da tarefa"
                        :manualInput="false"
                        :step-minute="5"
                        :min-date="new Date(dayjs($form.inicio).add(5, 'minute'))"
                        input-id="fim"
                        name="fim"
                        date-format="dd/mm/yy"
                        show-icon
                        show-time
                    />
                </IftaLabel>

                <Message v-if="$form.fim?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.fim.error?.message }}
                </Message>
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Colaboradores</span>

                <Chip v-for="colaborador in tarefaLocal.colaboradores">
                    {{ colaborador.nome_completo }}
                </Chip>

                <p v-if="tarefaLocal.colaboradores.length === 0" class="empty-message">
                    Sem colaboradores
                </p>
            </div>

            <div class="popover-info">
                <span style="font-weight: bold;">Máquinas</span>

                <Chip v-for="maquina in tarefaLocal.maquinas">
                    {{ maquina.nome }}
                </Chip>

                <p v-if="tarefaLocal.colaboradores.length === 0" class="empty-message">
                    Sem máquinas
                </p>
            </div>

            <div class="button-container">
                <Button label="Salvar" type="submit" rounded />

                <Button
                    title="Excluir Tarefa"
                    icon="pi pi-trash"
                    severity="danger"
                    variant="text"
                    rounded
                    @click="confirmarExclusaoTarefa()"
                />
            </div>
        </Form>
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

.popover-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.popover-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.empty-message {
    margin: 3px;
    color: #bbb;
    font-style: italic;
}

.button-container {
    width: 100%;
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>
