<script setup>
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";
import DataTable from "primevue/datatable";
import Button from "primevue/button";
import Column from "primevue/column";
import AdicionarColaborador from "@/pages/home/components/AdicionarColaborador.vue";
import api from "@/axios.js";
import Toast from "primevue/toast";

const colaboradores = ref([]);

const loading = ref(false);

const dialogAdicionarColaborador = ref(null);

const toast = useToast();

const getColaboradores = async () => {
    loading.value = true;

    try {
        const resp = await api.get('/colaboradores');

        colaboradores.value = resp.data.data;
    } catch (e) {
        loading.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar colaboradores', life: 3000 });

        return;
    }

    loading.value = false;
}

const adicionarColaborador = () => {
    dialogAdicionarColaborador.value.openDialog();
}

onMounted(() => {
    getColaboradores();
});
</script>

<template>
    <Toast />

    <AdicionarColaborador ref="dialogAdicionarColaborador" />

    <main>
        <header>
            <h1>Colaboradores</h1>

            <Button title="Adicionar Colaborador" icon="pi pi-plus" rounded @click="adicionarColaborador()" />
        </header>

        <DataTable :value="colaboradores" :loading="loading">
            <template #empty>
                <p style="text-align: center;">Não existem colaboradores cadastrados</p>
            </template>

            <Column header="Nome" field="nome_completo" />

            <Column header="Tarefas">
                <template #body="{ data }">
                    {{ data.tarefas.length }}
                </template>
            </Column>
        </DataTable>
    </main>
</template>

<style scoped>
header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>
