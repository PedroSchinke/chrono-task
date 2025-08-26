<script setup>
import { ref } from "vue";
import { useMaquinasStore } from "@/stores/maquinas.js";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import AdicionarMaquina from "./AdicionarMaquina.vue";

const maquinasStore = useMaquinasStore();

const loadingMaquinas = ref(false);

const adicionarMaquinaDialog = ref(null);

const openAdicionarMaquina = () => {
    adicionarMaquinaDialog.value.openDialog();
}
</script>

<template>
    <AdicionarMaquina ref="adicionarMaquinaDialog" />

    <main>
        <header>
            <h1>Máquinas</h1>

            <Button title="Adicionar Máquina" icon="pi pi-plus" rounded @click="openAdicionarMaquina()" />
        </header>

        <DataTable :value="maquinasStore.data.data" :loading="loadingMaquinas">
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
