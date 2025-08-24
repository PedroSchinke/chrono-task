<script setup>
import { reactive, ref, nextTick } from 'vue';
import { useTarefasStore } from "@/stores/tarefas.js";
import { useLoadingStore } from "@/stores/loading.js";
import { useColaboradoresStore } from "@/stores/colaboradores.js";
import { Form } from "@primevue/forms";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import api from "@/axios.js";
import dayjs from "dayjs";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";
import Popover from "primevue/popover";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import IftaLabel from "primevue/iftalabel";
import InputText from "primevue/inputtext";
import ColorPicker from "primevue/colorpicker";
import Textarea from "primevue/textarea";
import DatePicker from "primevue/datepicker";
import Message from "primevue/message";
import Chip from "primevue/chip";
import ProgressSpinner from "primevue/progressspinner";
import Button from "primevue/button";
import SelectColaboradores from "@/pages/home/components/SelectColaboradores.vue";
import SelectMaquinas from "@/pages/home/components/SelectMaquinas.vue";

const props = defineProps(['tarefa']);

const emits = defineEmits(['change-color']);

const tarefasStore = useTarefasStore();
const colaboradoresStore = useColaboradoresStore();
const loading = useLoadingStore();

const tarefaPopover = ref(null);
const selectColaboradores = ref(null);
const selectMaquinas = ref(null);

const isPopoverDismissable = ref(true);

const toast = useToast();
const confirm = useConfirm();

const salvando = ref(false);

const activeMessage = ref(false);
const message = ref('');

const form = reactive({
    titulo: props.tarefa.titulo ?? '',
    descricao: props.tarefa.descricao ?? '',
    inicio: new Date(props.tarefa.inicio),
    fim: new Date(props.tarefa.fim),
    colaboradores: props.tarefa.colaboradores,
    maquinas: props.tarefa.maquinas,
    cor: props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor
});

const cor = ref(props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor);

const resolver = ({ values, valid }) => {
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

    return { values, errors, valid };
}

const toggle = (event) => {
    tarefaPopover.value.toggle(event);

    nextTick(() => {
        tarefaPopover.value.alignOverlay();
    });
}

const hidePopover = () => {
    if (cor.value !== form.cor) {
        salvarCor();
    }

    hideMessage();
    resetarDados();
}

const salvarAlteracoesTarefa = async () => {
    loading.show('Salvando Alterações...');

    try {
        validarHorarios();

        const params = {
            titulo: form.titulo,
            descricao: form.descricao,
            inicio: form.inicio,
            fim: form.fim,
            colaboradores: form.colaboradores,
            maquinas: form.maquinas,
            cor: form.cor
        }

        await api.post(`/tarefa/${tarefaLocal.value.id}`, params);

        loading.hide();

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa editada com sucesso', life: 3000 });

        tarefasStore.getTarefas();
    } catch (e) {
        loading.hide();

        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }
    }
}

const salvarDados = async (key, label, genero = 'o') => {
    hideMessage();

    const values = { ...form };

    const { errors } = resolver({ values });

    if (key === 'inicio' || key === 'fim') {
        if (form[key].toISOString() === new Date(props.tarefa[key]).toISOString()) {
            return;
        }

        try {
            validarDisponibilidade();
        } catch (e) {
            toast.add({
                summary: e.title,
                detail: e.message,
                severity: 'error',
                life: 5000
            });

            form[key] = props.tarefa[key];

            return;
        }
    }

    if (errors[key] || form[key] === props.tarefa[key]) {
        return;
    }

    salvando.value = true;

    const params = {};

    params[key] = form[key];

    try {
        await api.post(`/tarefa/${props.tarefa.id}/${key}/alterar`, params);

        tarefasStore.updateTarefa(props.tarefa.id, params);

        salvando.value = false;

        showMessage(`${label} atualizad${genero}!`);
    } catch (e) {
        salvando.value = false;

        form[key] = props.tarefa[key];

        toast.add({
            summary: 'Algo deu errado...',
            detail: `Não foi possível salvar ${label}.`,
            severity: 'error',
            life: 5000
        });
    }
}

const validarDisponibilidade = () => {
    form.colaboradores.forEach((colaborador) => {
        const colaboradorComTarefas = colaboradoresStore.data.data.find((col) => {
            return col.id === colaborador.id;
        });

        console.log(colaboradorComTarefas)

        colaboradorComTarefas.tarefas.forEach((tarefa) => {
            const inicioExistente = new Date(tarefa.inicio);
            console.log(inicioExistente);
            const fimExistente = new Date(tarefa.fim);
            console.log(fimExistente);

            const inicioNovo = new Date(form.inicio);
            console.log(inicioNovo);
            const fimNovo = new Date(form.fim);
            console.log(fimNovo);

            const conflito = inicioNovo < fimExistente && fimNovo > inicioExistente;

            if (conflito) {
                throw new HorarioIndisponivelError(
                    'Conflito',
                    `${colaborador.nome_completo} não está disponível para o período.`
                );
            }
        })
    });
}

const confirmarExclusaoTarefa = async () => {
    confirm.require({
        header: 'Confirmação',
        message: `Tem certeza que deseja excluir a tarefa "${props.tarefa.titulo}"?`,
        rejectProps: {
            label: 'Não',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Sim'
        },
        accept: () => {
            excluirTarefa();
        }
    });
}

const excluirTarefa = async () => {
    loading.show('Excluindo Tarefa...');

    try {
        await api.post(`/tarefa/deletar/${props.tarefa.id}`);

        loading.hide();

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa excluída com sucesso', life: 3000 });

        tarefasStore.getTarefas();
    } catch (e) {
        loading.hide();

        toast.add({ severity: 'error', summary: 'Erro', detail: e.response.data.message, life: 3000 });
    }
}

const adicionarColaboradores = ({ values }) => {
    isPopoverDismissable.value = true;

    values.forEach((value) => {
        const colaboradorJaSelecionado = form.colaboradores.some((colaborador) => {
            return colaborador.id === value.id;
        });

        if (colaboradorJaSelecionado) {
            toast.add({
                summary: 'Atenção',
                detail: `${value.nome_completo} já está selecionado`,
                severity: 'warn',
                life: 3000
            });

            return;
        }

        form.colaboradores.push(value);
    });
}

const removerColaborador = (colaboradorId) => {
    form.colaboradores = form.colaboradores.filter((colaborador) => {
        return colaborador.id !== colaboradorId;
    });
}

const adicionarMaquinas = ({ values }) => {
    isPopoverDismissable.value = true;

    values.forEach((value) => {
        const maquinaJaSelecionada = form.maquinas.some((maquina) => {
            return maquina.id === value.id;
        });

        if (maquinaJaSelecionada) {
            toast.add({
                summary: 'Atenção',
                detail: `${value.nome} já está selecionado(a)`,
                severity: 'warn',
                life: 3000
            });

            return;
        }

        form.maquinas.push(value);
    });
}

const removerMaquina = (maquinaId) => {
    form.maquinas = form.maquinas.filter((maquina) => {
        return maquina.id !== maquinaId;
    });
}

const salvarCor = async () => {
    const params = { cor: form.cor };

    try {
        await api.post(`/tarefa/${props.tarefa.id}/cor/alterar`, params);

        tarefasStore.updateTarefa(props.tarefa.id, params);
    } catch (e) {
        form.cor = props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor;

        toast.add({
            summary: 'Algo deu errado...',
            detail: 'Não foi possível salvar cor.',
            severity: 'error',
            life: 5000
        });
    }
}

const changeColor = () => {
    emits('change-color', form.cor);
}

const openSelectColaboradores = () => {
    isPopoverDismissable.value = false;
    selectColaboradores.value.openDialog();
}

const openSelectMaquinas = () => {
    isPopoverDismissable.value = false;
    selectMaquinas.value.openDialog();
}

const showMessage = (msg = 'Dados salvos!') => {
    activeMessage.value = true;
    message.value = (msg);
}

const hideMessage = () => activeMessage.value = false;

const resetarDados = () => {
    form.titulo = props.tarefa.titulo;
    form.descricao = props.tarefa.descricao;
    form.colaboradores = props.tarefa.colaboradores;
    form.maquinas = props.tarefa.maquinas;
    form.inicio = new Date(props.tarefa.inicio);
    form.fim = new Date(props.tarefa.fim);
    form.cor = props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor;
}

defineExpose({ toggle });
</script>

<template>
    <SelectColaboradores
        ref="selectColaboradores"
        @on-select="(colaboradores) => adicionarColaboradores(colaboradores)"
        @on-close="isPopoverDismissable = true"
    />

    <SelectMaquinas
        ref="selectMaquinas"
        @on-select="(maquinas) => adicionarMaquinas(maquinas)"
        @on-close="isPopoverDismissable = true"
    />

    <Popover
        ref="tarefaPopover"
        :dismissable="isPopoverDismissable"
        @show="resetarDados()"
        @hide="hidePopover()"
    >
        <Tabs value="0">
            <TabList>
                <Tab value="0">Geral</Tab>
                <Tab value="1">Colaboradores</Tab>
                <Tab value="2">Máquinas</Tab>
            </TabList>

            <TabPanels>
                <TabPanel value="0">
                    <Form
                        v-slot="$form"
                        :initial-values="form"
                        :resolver
                        class="popover-form"
                        @submit="salvarAlteracoesTarefa"
                    >
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <IftaLabel style="width: 100%;">
                                    <label for="titulo">Título</label>

                                    <InputText
                                        v-model="form.titulo"
                                        id="titulo"
                                        name="titulo"
                                        fluid
                                        @blur="salvarDados('titulo', 'Título')"
                                    />
                                </IftaLabel>

                                <ColorPicker
                                    v-model="form.cor"
                                    title="Selecionar Cor"
                                    input-id="cor"
                                    name="cor"
                                    format="hex"
                                    @change="changeColor()"
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
                                <label for="descricao">Descrição (máx. 500)</label>

                                <Textarea
                                    v-model="form.descricao"
                                    id="descricao"
                                    name="descricao"
                                    style="resize: none;"
                                    fluid
                                    :auto-resize="true"
                                    :maxlength="500"
                                    @blur="salvarDados('descricao', 'Descrição', 'a')"
                                />
                            </IftaLabel>
                        </div>

                        <div class="popover-info">
                            <IftaLabel style="width: 100%;">
                                <label for="inicio">Início</label>

                                <DatePicker
                                    v-model="form.inicio"
                                    placeholder="Início da tarefa"
                                    :manualInput="false"
                                    :step-minute="5"
                                    input-id="inicio"
                                    name="inicio"
                                    date-format="dd/mm/yy"
                                    show-icon
                                    show-time
                                    fluid
                                    @hide="salvarDados('inicio', 'Início')"
                                />
                            </IftaLabel>

                            <Message
                                v-if="$form.inicio?.invalid"
                                severity="error"
                                size="small"
                                variant="simple"
                                class="input-message"
                            >
                                {{ $form.inicio.error?.message }}
                            </Message>
                        </div>

                        <div class="popover-info">
                            <IftaLabel style="width: 100%;">
                                <label for="fim">Fim</label>

                                <DatePicker
                                    v-model="form.fim"
                                    placeholder="Início da tarefa"
                                    :manualInput="false"
                                    :step-minute="5"
                                    :min-date="new Date(dayjs($form.inicio).add(5, 'minute'))"
                                    input-id="fim"
                                    name="fim"
                                    date-format="dd/mm/yy"
                                    show-icon
                                    show-time
                                    fluid
                                    @hide="salvarDados('fim', 'Fim')"
                                />
                            </IftaLabel>

                            <Message
                                v-if="$form.fim?.invalid"
                                severity="error"
                                size="small"
                                variant="simple"
                                class="input-message"
                            >
                                {{ $form.fim.error?.message }}
                            </Message>
                        </div>
                    </Form>
                </TabPanel>

                <TabPanel value="1">
                    <div class="popover-info">
                        <div style="display: flex; align-items: center; gap: 3px;">
                            <span style="font-weight: bold;">Colaboradores</span>

                            <Button
                                title="Adicionar Colaboradores"
                                icon="pi pi-plus"
                                rounded
                                variant="text"
                                size="small"
                                @click="openSelectColaboradores()"
                            />
                        </div>

                        <Chip
                            v-for="colaborador in form.colaboradores"
                            :key="colaborador.id"
                            removable
                            @remove="removerColaborador(colaborador.id)"
                        >
                            {{ colaborador.nome_completo }}
                        </Chip>

                        <p v-if="form.colaboradores.length === 0" class="empty-message">
                            Sem colaboradores
                        </p>
                    </div>
                </TabPanel>

                <TabPanel value="2">
                    <div class="popover-info">
                        <div style="display: flex; align-items: center; gap: 3px;">
                            <span style="font-weight: bold;">Máquinas</span>

                            <Button
                                title="Adicionar Máquinas"
                                icon="pi pi-plus"
                                rounded
                                variant="text"
                                size="small"
                                @click="openSelectMaquinas()"
                            />
                        </div>

                        <Chip
                            v-for="maquina in form.maquinas"
                            :key="maquina.id"
                            removable
                            @remove="removerMaquina(maquina.id)"
                        >
                            {{ maquina.nome }}
                        </Chip>

                        <p v-if="form.maquinas.length === 0" class="empty-message">
                            Sem máquinas
                        </p>
                    </div>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <div class="popover-footer">
            <div v-if="salvando" class="spinner-container">
                <ProgressSpinner style="width: 20px; height: 20px; color: #ccc" />

                <span>Salvando...</span>
            </div>

            <Message
                v-if="activeMessage"
                severity="success"
                size="medium"
                variant="simple"
            >
                {{ message }}
            </Message>

            <Button
                title="Excluir Tarefa"
                icon="pi pi-trash"
                severity="danger"
                variant="text"
                class="botao-excluir"
                rounded
                @click="confirmarExclusaoTarefa()"
            />
        </div>
    </Popover>
</template>

<style scoped>
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

.popover-footer {
    width: 100%;
    height: 48px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spinner-container {
    display: flex;
    align-items: center;
    gap: 5px;
}

.botao-excluir {
    position: absolute;
    right: 16px;
}

:deep(.p-tabpanels) {
    padding: 20px 0;
}

:deep(.p-chip) {
    width: fit-content;
}
</style>
