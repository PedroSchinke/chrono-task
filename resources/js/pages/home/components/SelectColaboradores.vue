<script setup>
import { reactive, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { formatCpf } from "@/helpers/stringHelper.js";
import Dialog from "primevue/dialog";
import Fieldset from "primevue/fieldset";
import Chip from "primevue/chip";
import IftaLabel from "primevue/iftalabel";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Paginator from "primevue/paginator";
import api from "@/axios.js";

const emit = defineEmits(['onSelect', 'onClose']);

const colaboradores = ref([]);
const colaboradoresSelecionados = ref([]);

const dialogVisible = ref(false);

const loading = ref(false);

const toast = useToast();

const filter = reactive({
    codigo: '',
    nome: '',
    cpf: ''
});

const pagination = reactive({
    current_page: 1,
    per_page: 5,
    total: 0,
    first: 0
});

const sort = reactive({
    sort_field: 'nome_completo',
    sort_order: 'asc'
});

const getColaboradores = async ({ rows, page } = { rows: 5, page: 0 }) => {
    loading.value = true;

    const params = new URLSearchParams({
        id: filter.codigo === null ? '' : filter.codigo,
        nome_completo: filter.nome,
        cpf: filter.cpf === null ? '' : filter.cpf,
        per_page: rows,
        page: ++page,
        sort_field: sort.sort_field,
        sort_order: sort.sort_order
    });

    try {
        const resp = await api.get('/colaboradores?' + params.toString());

        const data = resp.data.data;

        colaboradores.value = data.data;

        handlePagination(data);

        loading.value = false;
    } catch (e) {
        loading.value = false;

        toast.add({
            summary: 'Algo deu errado...',
            detail: 'Não foi possível buscar colaboradores.',
            severity: 'error',
            life: 3000
        });
    }
}

const handlePagination = (data) => {
    pagination.current_page = data.current_page;
    pagination.per_page = data.per_page;
    pagination.total = data.total;
    pagination.first = data.from;
}

const onSort = ({ sortField, sortOrder }) => {
    sort.sort_field = sortField;
    sort.sort_order = sortOrder === 1 ? 'asc' : 'desc';

    getColaboradores({ page: 0, rows: pagination.per_page });
}

const adicionaColaborador = ({ data }) => {
    const colaboradorJaSelecionado = colaboradoresSelecionados.value.some((colaborador) => {
        return colaborador.id === data.id;
    });

    if (colaboradorJaSelecionado) {
        toast.add({
            summary: 'Atenção',
            detail: `${data.nome_completo} já está selecionado(a).`,
            severity: 'warn',
            life: 3000
        });

        return;
    }

    colaboradoresSelecionados.value.push(data);
}

const adicionar = () => {
    emit('onSelect', { values: colaboradoresSelecionados.value });

    closeDialog();
}

const removeColaborador = (colaborador) => {
    colaboradoresSelecionados.value = colaboradoresSelecionados.value.filter((el) => {
        return el.id !== colaborador.id;
    });
}

const limparSelecionados = () => {
    colaboradoresSelecionados.value = [];
}

const limparFiltro = (pesquisar = false) => {
    filter.codigo = '';
    filter.nome = '';
    filter.cpf = '';

    if (pesquisar) {
        getColaboradores();
    }
}

const openDialog = () => {
    colaboradores.value = [];
    colaboradoresSelecionados.value = [];
    limparFiltro();

    dialogVisible.value = true;
}

const closeDialog = () => {
    colaboradores.value = [];
    colaboradoresSelecionados.value = [];
    limparFiltro();

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <Dialog
        header="Selecionar Colaboradores"
        v-model:visible="dialogVisible"
        :style="{ width: '90%' }"
        :content-style="{ padding: '0 24px' }"
        @show="getColaboradores()"
        @hide="$emit('onClose')"
    >
        <Fieldset legend="Pesquisar" style="margin-top: -15px;">
            <div class="filter-container" @keyup.enter="getColaboradores()">
                <IftaLabel>
                    <InputNumber v-model="filter.codigo" mode="decimal" input-id="codigo" name="codigo" fluid />
                    <label for="codigo">Código</label>
                </IftaLabel>

                <IftaLabel>
                    <InputText v-model="filter.nome" input-id="nome" name="nome" fluid />
                    <label for="nome">Nome</label>
                </IftaLabel>

                <IftaLabel>
                    <InputNumber v-model="filter.cpf" mode="decimal" input-id="cpf" name="cpf" fluid />
                    <label for="cpf">CPF</label>
                </IftaLabel>

                <Button label="Pesquisar" icon="pi pi-search" rounded style="margin-left: 10px;" @click="getColaboradores()" />

                <Button title="Limpar Filtro" icon="pi pi-filter-slash" variant="text" rounded @click="limparFiltro(true)" />
            </div>
        </Fieldset>

        <Fieldset legend="Selecionados" style="margin-bottom: 10px;">
            <div class="selecionados-container">
                <Chip
                    v-for="colaborador of colaboradoresSelecionados"
                    :key="colaborador.id"
                    removable
                    @remove="removeColaborador(colaborador)"
                >
                    {{ colaborador.nome_completo }}
                </Chip>

                <p v-if="colaboradoresSelecionados.length === 0" style="margin: 0; font-style: italic;">
                    Clique em um colaborador para adicioná-lo
                </p>

                <Button
                    v-else
                    label="Limpar"
                    icon="pi pi-trash"
                    variant="text"
                    rounded
                    @click="limparSelecionados()"
                />
            </div>
        </Fieldset>

        <DataTable
            :value="colaboradores"
            :loading="loading"
            selection-mode="multiple"
            scrollable
            scrollHeight="300px"
            @row-select="adicionaColaborador($event)"
            @sort="onSort($event)"
        >
            <template #empty>
                <p style="text-align: center;">Não foram encontrados colaboradores</p>
            </template>

            <Column header="Código" field="id" :sortable="true" />

            <Column header="Nome" field="nome_completo" :sortable="true" />

            <Column header="CPF" field="cpf" :sortable="true">
                <template #body="{ data }">
                    {{ formatCpf(data.cpf) }}
                </template>
            </Column>
        </DataTable>

        <Paginator
            :rows="pagination.per_page"
            :total-records="pagination.total"
            :first="pagination.first"
            :rows-per-page-options="[5, 10, 20, 50, 100]"
            @page="getColaboradores($event)"
        />

        <Button label="Adicionar" class="adicionar-button" rounded @click="adicionar()" />
    </Dialog>
</template>

<style scoped>
.selecionados-container {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.selecionados-container h3 {
    margin: 0;
}

.filter-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.adicionar-button {
    position: absolute;
    bottom: 10px;
    right: 20px;
}
</style>
