<script setup>
import { ref, onMounted, reactive } from "vue";
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";
import Toast from 'primevue/toast';
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import AdicionarMaquina from "./AdicionarMaquina.vue";

const maquinas = ref([]);

const colunas = ref([
    { label: 'Segunda', name: 'segunda' },
    { label: 'Terça', name: 'terca' },
    { label: 'Quarta', name: 'quarta' },
    { label: 'Quinta', name: 'quinta' },
    { label: 'Sexta', name: 'sexta' },
    { label: 'Sabado', name: 'sabado' },
    { label: 'Domingo', name: 'domingo' },
])

const pagination = reactive({
    page: 1,
    perPage: 100,
    offset: 0,
    total: 0
});

const loadingMaquinas = ref(false);

const adicionarMaquinaDialog = ref(null);

const toast = useToast();

onMounted(() => {
    getMaquinas();
});

const getMaquinas = async () => {
    loadingMaquinas.value = true;

    try {
        const resp = await api.get('/maquinas');

        maquinas.value = resp.data.data;
        handlePagination(resp.data);
    } catch (e) {
        loadingMaquinas.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar máquinas', life: 3000 });
    }

    loadingMaquinas.value = false;
}

const exibirHorariosDisponiveis = (diaSemana, maquina, chave) => {
    if (!maquina.horarios_disponiveis) return '';

    const horario = maquina.horarios_disponiveis.find(h => h.dia_semana === diaSemana);
    return horario ? horario[chave].substring(0, 5) : '';
}

const adicionarMaquina = () => {
    adicionarMaquinaDialog.value.openDialog();
}

const handlePagination = (data) => {
    pagination.page = data.current_page;
    pagination.perPage = data.per_page;
    pagination.offset = data.offset;
    pagination.total = data.total;
}
</script>

<template>
    <Toast />

    <AdicionarMaquina ref="adicionarMaquinaDialog" @recarregar-maquinas="getMaquinas()" />

    <main>
        <header>
            <h1>Máquinas</h1>

            <Button title="Adicionar Máquina" icon="pi pi-plus" rounded @click="adicionarMaquina()" />
        </header>

        <DataTable :value="maquinas" :loading="loadingMaquinas" >
            <Column header="Máquina" field="nome" />

            <Column v-for="coluna of colunas" :header="coluna.label">
                <template #body="{ data }">
                    {{ exibirHorariosDisponiveis(coluna.name, data, 'hora_inicio') }}
                    -
                    {{ exibirHorariosDisponiveis(coluna.name, data, 'hora_fim') }}
                </template>
            </Column>
        </DataTable>
    </main>
</template>

<style scoped>
main {
    display: flex;
    flex-direction: column;
}

header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>
