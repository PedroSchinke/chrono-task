<script setup>
import { ref } from "vue";
import DataTable from "primevue/datatable";
import Button from "primevue/button";
import Column from "primevue/column";
import AdicionarColaborador from "@/pages/home/components/AdicionarColaborador.vue";

const colaboradores = ref([]);

const loading = ref(false);

const dialogAdicionarColaborador = ref(null);

const adicionarColaborador = () => {
    dialogAdicionarColaborador.value.openDialog();
}
</script>

<template>
    <AdicionarColaborador ref="dialogAdicionarColaborador" />

    <main>
        <header>
            <h1>Colaboradores</h1>

            <Button title="Adicionar Colaborador" icon="pi pi-plus" rounded @click="adicionarColaborador()" />
        </header>

        <DataTable :value="colaboradores" :loading="loading">
            <template #empty>
                <p style="text-align: center;">Não existem colaboradores cadastradas</p>
            </template>

            <Column header="Nome" field="nome_completo" />

            <Column header="Tarefas">

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
