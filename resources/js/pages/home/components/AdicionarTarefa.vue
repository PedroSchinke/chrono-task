<script setup>
import { reactive, ref, watch } from "vue";
import { useToast } from 'primevue/usetoast';
import { Form } from "@primevue/forms";
import api from '@/axios';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import Dialog from "primevue/dialog";
import IftaLabel from "primevue/iftalabel";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import ColorPicker from "primevue/colorpicker";
import Textarea from "primevue/textarea";
import DatePicker from 'primevue/datepicker';
import Fieldset from "primevue/fieldset";
import Chip from "primevue/chip";
import Button from "primevue/button";
import ModalLoading from "@/components/ModalLoading.vue";
import SelectColaboradores from "@/pages/home/components/SelectColaboradores.vue";
import SelectMaquinas from "@/pages/home/components/SelectMaquinas.vue";

dayjs.extend(customParseFormat);

const emits = defineEmits(['recarregar-tarefas']);

const dialogVisible = ref(false);

const form = reactive({
    titulo: '',
    descricao: '',
    inicio: new Date(),
    fim: new Date(dayjs().add(1, 'day')),
    colaboradores: [],
    maquinas: [],
    cor: 'ff0000'
});

const loading = ref(false);
const loadingMessage = ref('Adicionando Tarefa...');

const selectColaboradores = ref(null);
const selectMaquinas = ref(null);

const toast = useToast();

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

    return { values, errors };
}

const adicionarTarefa = async ({ valid }) => {
    if (!valid) {
        toast.add({ summary: 'Atenção', detail: 'Preencha todos os campos.', severity: 'error', life: 3000 });

        return;
    }

    loading.value = true;

    const params = {
        titulo: form.titulo,
        descricao: form.descricao,
        inicio: form.inicio,
        fim: form.fim,
        colaboradores: form.colaboradores,
        maquinas: form.maquinas,
        cor: '#' + form.cor
    }

    try {
        await api.post('/tarefa', params);
    } catch (e) {
        loading.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar tarefa', life: 3000 });

        return;
    }

    loading.value = false;

    toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Tarefa adicionada com sucesso', life: 3000 });

    emits('recarregar-tarefas');

    closeDialog();
}

const adicionarColaboradores = ({ values }) => {
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

const adicionarMaquinas = ({ values }) => {
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

const removerColaborador = (colaborador) => {
    form.colaboradores = form.colaboradores.filter((el) => {
        return el.id !== colaborador.id;
    });
}

const removerMaquina = (maquina) => {
    form.maquinas = form.maquinas.filter((el) => {
        return el.id !== maquina.id;
    });
}

const limparColaboradoresSelecionados = () => {
    form.colaboradores = [];
}

const limparMaquinasSelecionadas = () => {
    form.maquinas = [];
}

const openSelectColaboradores = () => {
    selectColaboradores.value.openDialog();
}

const openSelectMaquinas = () => {
    selectMaquinas.value.openDialog();
}

const limparCampos = () => {
    form.titulo = '';
    form.descricao = '';
    form.datas = [];
    form.periodo_diario_inicio = dayjs('08:00', 'HH:mm').toDate();
    form.periodo_diario_fim = dayjs('18:00', 'HH:mm').toDate();
    form.maquina = {};
    form.cor = 'ff0000';
}

const openDialog = () => {
    limparCampos();
    dialogVisible.value = true;
}

const closeDialog = () => {
    limparCampos();
    dialogVisible.value = false;
}

watch([ () => form.datas, () => form.periodo_diario_inicio, () => form.periodo_diario_fim ], () => {
    form.maquina = {};
});

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <ModalLoading :is-loading="loading" :message="loadingMessage" />

    <SelectColaboradores ref="selectColaboradores" @on-select="(colaboradores) => adicionarColaboradores(colaboradores)" />

    <SelectMaquinas ref="selectMaquinas" @on-select="(maquinas) => adicionarMaquinas(maquinas)" />

    <Dialog header="Adicionar Tarefa" :style="{ width: '60%' }" v-model:visible="dialogVisible">
        <Form v-slot="$form" :initial-values="form" :resolver class="dialog-content" @submit="adicionarTarefa">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <IftaLabel style="width: 100%;">
                        <label for="titulo">Título</label>

                        <InputText id="titulo" name="titulo" v-model="form.titulo" fluid />
                    </IftaLabel>

                    <ColorPicker v-model="form.cor" title="Selecionar Cor" input-id="cor-input" format="hex" />
                </div>

                <Message v-if="$form.titulo?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.titulo.error?.message }}
                </Message>
            </div>

            <IftaLabel>
                <label for="descricao">Descrição (máx. 500)</label>

                <Textarea
                    v-model="form.descricao"
                    id="descricao"
                    name="descricao"
                    style="resize: none;"
                    fluid
                    :auto-resize="true"
                    :maxlength="500"
                />
            </IftaLabel>

            <div style="display: flex; align-items: center; gap: 10px;">
                <IftaLabel>
                    <label for="inicio">Início</label>

                    <DatePicker
                        v-model="form.inicio"
                        :manualInput="false"
                        input-id="titulo"
                        date-format="dd/mm/yy"
                        show-icon
                        show-button-bar
                        show-time
                        :step-minute="5"
                        @clear-click="form.inicio = new Date()"
                    />

                    <Message v-if="$form.inicio?.invalid" severity="error" size="small" variant="simple" class="input-message">
                        {{ $form.inicio.error?.message }}
                    </Message>
                </IftaLabel>

                <IftaLabel>
                    <label for="fim">Fim</label>

                    <DatePicker
                        v-model="form.fim"
                        :manualInput="false"
                        :min-date="new Date(dayjs(form.inicio).add(5, 'minute'))"
                        input-id="fim"
                        date-format="dd/mm/yy"
                        show-icon
                        show-button-bar
                        show-time
                        :step-minute="5"
                        @clear-click="form.fim = new Date()"
                    />

                    <Message v-if="$form.fim?.invalid" severity="error" size="small" variant="simple" class="input-message">
                        {{ $form.fim.error?.message }}
                    </Message>
                </IftaLabel>
            </div>

            <Fieldset style="margin-top: -15px;">
                <template #legend style="display: flex;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h4 style="margin: 0; color: #ccc;">Colaboradores Responsáveis</h4>

                        <Button
                            title="Adicionar Colaborador"
                            icon="pi pi-plus"
                            variant="text"
                            size="small"
                            rounded
                            @click="openSelectColaboradores()"
                        />
                    </div>
                </template>

                <div class="chips-container">
                    <Chip
                        v-for="colaborador of form.colaboradores"
                        :key="colaborador.id"
                        removable
                        @remove="removerColaborador(colaborador)"
                    >
                        {{ colaborador.nome_completo }}
                    </Chip>

                    <p v-if="form.colaboradores.length === 0" style="margin: 0; font-style: italic;">
                        Nenhum colaborador selecionado
                    </p>

                    <Button
                        v-else
                        label="Limpar"
                        icon="pi pi-trash"
                        variant="text"
                        rounded
                        @click="limparColaboradoresSelecionados()"
                    />
                </div>
            </Fieldset>

            <Fieldset style="margin-top: -10px;">
                <template #legend style="display: flex;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h4 style="margin: 0; color: #ccc;">Máquinas Utilizadas</h4>

                        <Button
                            title="Adicionar Máquina"
                            icon="pi pi-plus"
                            variant="text"
                            size="small"
                            rounded
                            @click="openSelectMaquinas()"
                        />
                    </div>
                </template>

                <div class="chips-container">
                    <Chip
                        v-for="maquina of form.maquinas"
                        :key="maquina.id"
                        removable
                        @remove="removerMaquina(maquina)"
                    >
                        {{ maquina.nome }}
                    </Chip>

                    <p v-if="form.maquinas.length === 0" style="margin: 0; font-style: italic;">
                        Nenhuma máquina selecionada
                    </p>

                    <Button
                        v-else
                        label="Limpar"
                        icon="pi pi-trash"
                        variant="text"
                        rounded
                        @click="limparMaquinasSelecionadas()"
                    />
                </div>
            </Fieldset>

            <Button label="Salvar" icon="pi pi-check" type="submit" style="margin-top: 10px;" />
        </Form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.input-message {
    margin-top: 3px;
}

.chips-container {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    gap: 10px;
}
</style>
