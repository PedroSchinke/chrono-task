<script setup>
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import AdicionarMaquina from "./AdicionarMaquina.vue";

const props = defineProps(['maquinas']);
const emit = defineEmits(['recarregarMaquinas', 'recarregarHorariosDisponiveis']);

const maquinas = ref([]);

const loadingMaquinas = ref(false);

const adicionarMaquinaDialog = ref(null);

const toast = useToast();

const getMaquinas = async () => {
    loadingMaquinas.value = true;

    try {
        const resp = await api.get('/maquinas');

        loadingMaquinas.value = false;

        maquinas.value = resp.data.data.data;
    } catch (e) {
        loadingMaquinas.value = false;

        toast.add({
            summary: 'Algo deu errado...',
            detail: 'Não foi possível carregar máquinas.',
            severity: 'error',
            life: 5000
        });
    }
}

const openAdicionarMaquina = () => {
    adicionarMaquinaDialog.value.openDialog();
}

const recarregarMaquinas = () => {
    emit('recarregarMaquinas');
    emit('recarregarHorariosDisponiveis');
}

onMounted(() => {
    getMaquinas();
})
</script>

<template>
    <AdicionarMaquina ref="adicionarMaquinaDialog" @recarregar-maquinas="recarregarMaquinas()" />

    <main>
        <header>
            <h1>Máquinas</h1>

            <Button title="Adicionar Máquina" icon="pi pi-plus" rounded @click="openAdicionarMaquina()" />
        </header>

        <DataTable :value="maquinas" :loading="loadingMaquinas">
            <template #empty>
                <p style="text-align: center;">Não existem máquinas cadastradas</p>
            </template>

            <Column header="Nome" field="nome" />

            <Column header="Tarefas" field="tarefas">
                <template #body="{ data }">
                    {{ data.tarefas.length }}
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
