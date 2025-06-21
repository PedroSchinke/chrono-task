<script setup>
import { ref } from "vue";
import Button from "primevue/button";
import AdicionarMaquina from "./AdicionarMaquina.vue";

const adicionarMaquinaDialog = ref(null);

const adicionarMaquina = () => {
    adicionarMaquinaDialog.value.openDialog();
}
</script>

<template>
    <AdicionarMaquina ref="adicionarMaquinaDialog" />

    <main>
        <header>
            <h1>Máquinas</h1>

            <Button title="Adicionar Máquina" icon="pi pi-plus" rounded @click="adicionarMaquina()" />
        </header>
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
