<script setup>
import { reactive, ref, watch } from "vue";
import { useToast } from 'primevue/usetoast';
import api from '@/axios';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import Toast from 'primevue/toast';
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import AutoComplete from 'primevue/autocomplete';
import DatePicker from 'primevue/datepicker';
import ColorPicker from "primevue/colorpicker";
import Button from "primevue/button";
import ModalLoading from "@/components/ModalLoading.vue";

dayjs.extend(customParseFormat);

const emits = defineEmits(['recarregar-tarefas']);

const maquinas = ref([]);

const dialogVisible = ref(false);

const form = reactive({
    titulo: '',
    descricao: '',
    datas: [],
    periodo_diario_inicio: dayjs('08:00', 'HH:mm').toDate(),
    periodo_diario_fim: dayjs('18:00', 'HH:mm').toDate(),
    maquina: {},
    cor: 'ff0000'
});

const loading = ref(false);
const loadingMessage = ref('Adicionando Tarefa...');
const loadingMaquinas = ref(false);

const toast = useToast();

const adicionarTarefa = async () => {
    try {
        validaCampos();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Alerta', detail: e.message, life: 3000 });

        return;
    }

    loading.value = true;

    const params = {
        titulo: form.titulo,
        descricao: form.descricao,
        inicio: form.datas[0],
        fim: form.datas[1],
        periodo_diario_inicio: dayjs(form.periodo_diario_inicio).toISOString(),
        periodo_diario_fim: dayjs(form.periodo_diario_fim).toISOString(),
        id_maquina: form.maquina.id,
        cor: '#' + form.cor
    }

    console.log(params);

    try {
        const resp = await api.post('/tarefa', params);
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

const getMaquinas = async ({ query = '' }) => {
    loadingMaquinas.value = true;

    const params = new URLSearchParams({
        nome: query,
        data_inicio: form.datas[0].toISOString(),
        data_fim: form.datas[1].toISOString(),
        periodo_diario_inicio: dayjs(form.periodo_diario_inicio).toISOString(),
        periodo_diario_fim: dayjs(form.periodo_diario_fim).toISOString()
    });

    try {
        const resp = await api.get(`/maquinas?${params.toString()}`);

        maquinas.value = resp.data.data;

        loadingMaquinas.value = false;
    } catch (e) {
        loadingMaquinas.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível pesquisar máquinas', life: 3000 });
    }
}

const validaCampos = () => {
    const camposPreenchidos = form.titulo &&
                              form.descricao &&
                              form.datas &&
                              form.periodo_diario_inicio &&
                              form.periodo_diario_fim &&
                              form.maquina &&
                              form.cor;

    if (!camposPreenchidos) {
        throw new Error('Preencha todos os campos para criar uma tarefa');
    }
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
    <Toast />

    <ModalLoading :is-loading="loading" :message="loadingMessage" />

    <Dialog header="Adicionar Tarefa" v-model:visible="dialogVisible">
        <form class="dialog-content">
            <InputText placeholder="Título" v-model="form.titulo"/>

            <InputText placeholder="Descrição" v-model="form.descricao"/>

            <DatePicker
                placeholder="Período da tarefa"
                v-model="form.datas"
                :manualInput="false"
                :min-date="new Date()"
                date-format="dd/mm/yy"
                show-icon
                selectionMode="range"
                show-button-bar
                show-time
                :step-minute="30"
                @clear-click="form.datas = []"
            />

            <span class="label">Período de atividade diário</span>

            <div class="hora-inputs-container">
                <div class="hora-input">
                    <label class="label" for="periodo-atividade-inicio-input">Das</label>

                    <DatePicker
                        v-model="form.periodo_diario_inicio"
                        input-id="periodo-atividade-inicio-input"
                        time-only
                        fluid
                        :step-minute="5"
                        style="width: 70px;"
                    />
                </div>

                <div class="hora-input">
                    <label class="label" for="periodo-atividade-fim-input">Até</label>

                    <DatePicker
                        v-model="form.periodo_diario_fim"
                        input-id="periodo-atividade-fim-input"
                        time-only
                        fluid
                        :step-minute="5"
                        style="width: 70px;"
                    />
                </div>
            </div>

            <AutoComplete
                placeholder="Máquina"
                v-model="form.maquina"
                :loading="loadingMaquinas"
                :suggestions="maquinas"
                :disabled="form.datas.length < 2 || !form.periodo_diario_inicio || !form.periodo_diario_fim"
                empty-search-message="Não existem máquinas disponíveis para o período escolhido."
                option-label="nome"
                dropdown
                force-selection
                @complete="getMaquinas($event)"
            />

            <div class="cor-input-container">
                <label class="label" for="cor-input">Cor</label>

                <ColorPicker v-model="form.cor" input-id="cor-input" format="hex" />
            </div>

            <Button label="Salvar" icon="pi pi-check" @click="adicionarTarefa()" />
        </form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dias-selecionados-container {
    max-width: 310px;
}

.dia-selecionado-container {
    display: flex;
    align-items: center;
}

.hora-inputs-container {
    display: flex;
    gap: 10px;
}

.hora-input {
    display: flex;
    align-items: center;
    gap: 5px;
}

.label {
    color: #c8c8c8;
}

.cor-input-container {
    display: flex;
    align-items: center;
    gap: 5px;
}
</style>
