<script setup>
import { reactive, ref } from "vue";
import { useToast } from "primevue/usetoast";
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

const maquinas = ref([]);
const maquinasSelecionadas = ref([]);

const dialogVisible = ref(false);

const loading = ref(false);

const toast = useToast();

const filter = reactive({
    codigo: '',
    nome: '',
    descricao: ''
});

const pagination = reactive({
    current_page: 1,
    per_page: 5,
    total: 0,
    first: 0
});

const sort = reactive({
    sort_field: 'nome',
    sort_order: 'asc'
});

const getMaquinas = async ({ rows, page } = { rows: 5, page: 0 }) => {
    loading.value = true;

    const params = new URLSearchParams({
        id: filter.codigo === null ? '' : filter.codigo,
        nome: filter.nome,
        descricao: filter.descricao,
        per_page: rows,
        page: ++page,
        sort_field: sort.sort_field,
        sort_order: sort.sort_order
    });

    try {
        const resp = await api.get('/maquinas?' + params.toString());

        const data = resp.data.data;

        maquinas.value = data.data;

        handlePagination(data);

        loading.value = false;
    } catch (e) {
        loading.value = false;

        toast.add({
            summary: 'Algo deu errado...',
            detail: 'Não foi possível buscar máquinas.',
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

    getMaquinas({ page: 0, rows: pagination.per_page });
}

const adicionarMaquina = ({ data }) => {
    const MaquinaJaSelecionada = maquinasSelecionadas.value.some((maquina) => {
        return maquina.id === data.id;
    });

    if (MaquinaJaSelecionada) {
        toast.add({
            summary: 'Atenção',
            detail: `${data.nome} já está selecionado(a).`,
            severity: 'warn',
            life: 3000
        });

        return;
    }

    maquinasSelecionadas.value.push(data);
}

const adicionar = () => {
    emit('onSelect', { values: maquinasSelecionadas.value });

    closeDialog();
}

const removeMaquina = (maquina) => {
    maquinasSelecionadas.value = maquinasSelecionadas.value.filter((el) => {
        return el.id !== maquina.id;
    });
}

const limparSelecionados = () => {
    maquinasSelecionadas.value = [];
}

const limparFiltro = (pesquisar = false) => {
    filter.codigo = '';
    filter.nome = '';
    filter.descricao = '';

    if (pesquisar) {
        getMaquinas();
    }
}

const openDialog = () => {
    maquinas.value = [];
    maquinasSelecionadas.value = [];
    limparFiltro();

    dialogVisible.value = true;
}

const closeDialog = () => {
    maquinas.value = [];
    maquinasSelecionadas.value = [];
    limparFiltro();

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <Dialog
        header="Selecionar Máquinas"
        v-model:visible="dialogVisible"
        :style="{ width: '90%' }"
        :content-style="{ padding: '0 24px' }"
        @show="getMaquinas()"
        @hide="$emit('onClose')"
    >
        <Fieldset legend="Pesquisar" style="margin-top: -15px;">
            <div class="filter-container" @keyup.enter="getMaquinas()">
                <IftaLabel>
                    <InputNumber v-model="filter.codigo" mode="decimal" input-id="codigo" name="codigo" fluid />
                    <label for="codigo">Código</label>
                </IftaLabel>

                <IftaLabel>
                    <InputText v-model="filter.nome" input-id="nome" name="nome" fluid />
                    <label for="nome">Nome</label>
                </IftaLabel>

                <IftaLabel>
                    <InputText v-model="filter.descricao" input-id="descricao" name="descricao" fluid />
                    <label for="descricao">Descrição</label>
                </IftaLabel>

                <Button label="Pesquisar" icon="pi pi-search" rounded style="margin-left: 10px;" @click="getMaquinas()" />

                <Button title="Limpar Filtro" icon="pi pi-filter-slash" variant="text" rounded @click="limparFiltro(true)" />
            </div>
        </Fieldset>

        <Fieldset legend="Selecionados" style="margin-bottom: 10px;">
            <div class="selecionados-container">
                <Chip
                    v-for="maquina of maquinasSelecionadas"
                    :key="maquina.id"
                    removable
                    @remove="removeMaquina(maquina)"
                >
                    {{ maquina.nome }}
                </Chip>

                <p v-if="maquinasSelecionadas.length === 0" style="margin: 0; font-style: italic;">
                    Clique em uma máquina para adicioná-la
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
            :value="maquinas"
            :loading="loading"
            selection-mode="multiple"
            scrollable
            scrollHeight="300px"
            @row-select="adicionarMaquina($event)"
            @sort="onSort($event)"
        >
            <template #empty>
                <p style="text-align: center;">Nenhuma máquina foi encontrada</p>
            </template>

            <Column header="Código" field="id" :sortable="true" />

            <Column header="Nome" field="nome" :sortable="true" />

            <Column header="Descrição" field="descricao" :sortable="true" />
        </DataTable>

        <Paginator
            :rows="pagination.per_page"
            :total-records="pagination.total"
            :first="pagination.first"
            :rows-per-page-options="[5, 10, 20, 50, 100]"
            @page="getMaquinas($event)"
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
