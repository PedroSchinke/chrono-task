<script setup>
import { reactive, ref } from 'vue';
import { Form } from "@primevue/forms";
import { useToast } from "primevue/usetoast";
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
import DatePicker from "primevue/datepicker";
import Message from "primevue/message";
import Chip from "primevue/chip";
import Button from "primevue/button";
import SelectColaboradores from "@/pages/home/components/SelectColaboradores.vue";
import SelectMaquinas from "@/pages/home/components/SelectMaquinas.vue";

const props = defineProps(['tarefa']);

const tarefaPopover = ref(null);
const selectColaboradores = ref(null);
const selectMaquinas = ref(null);

const isPopoverDismissable = ref(true);

const toast = useToast();

const form = reactive({
    titulo: props.tarefa.titulo,
    descricao: props.tarefa.descricao,
    inicio: new Date(props.tarefa.inicio),
    fim: new Date(props.tarefa.fim),
    colaboradores: props.tarefa.colaboradores,
    maquinas: props.tarefa.maquinas,
    cor: props.tarefa.cor.startsWith('#') ? props.tarefa.cor : '#' + props.tarefa.cor
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

const toggle = (event) => {
    tarefaPopover.value.toggle(event);
}

const salvarAlteracoesTarefa = async () => {
    loadingMessage.value = 'Salvando Alterações...';
    loading.value = true;

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

        loading.value = false;

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa editada com sucesso', life: 3000 });

        tarefasStore.getTarefas();
    } catch (e) {
        loading.value = false;

        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }
    }
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
    loadingMessage.value = 'Excluindo Tarefa...';
    loading.value = true;

    try {
        await api.post(`/tarefa/deletar/${props.tarefa.id}`);

        loading.value = false;

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa excluída com sucesso', life: 3000 });

        tarefasStore.getTarefas();
    } catch (e) {
        loading.value = false;

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

const openSelectColaboradores = () => {
    isPopoverDismissable.value = false;
    selectColaboradores.value.openDialog();
}

const openSelectMaquinas = () => {
    isPopoverDismissable.value = false;
    selectMaquinas.value.openDialog();
}

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
        @hide="resetarDados()"
        @show="resetarDados()"
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

                                    <InputText v-model="form.titulo" id="titulo" name="titulo" fluid />
                                </IftaLabel>

                                <ColorPicker
                                    v-model="form.cor"
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

                                <InputText v-model="form.descricao" id="descricao" name="descricao" fluid />
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
                                />
                            </IftaLabel>

                            <Message v-if="$form.fim?.invalid" severity="error" size="small" variant="simple" class="input-message">
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

.button-container {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

:deep(.p-tabpanels) {
    padding: 20px 0;
}
</style>
