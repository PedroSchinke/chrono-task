<script setup>
import { onMounted } from 'vue';
import { useTarefasStore } from '@/stores/tarefas';
import { useColaboradoresStore } from "@/stores/colaboradores.js";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import GraficoGantt from "./components/GraficoGantt.vue";
import Maquinas from "./components/Maquinas.vue";
import Colaboradores from "@/pages/home/components/Colaboradores.vue";
import ModalLoading from "@/components/ModalLoading.vue";

const tarefasStore = useTarefasStore();
const colaboradoresStore = useColaboradoresStore();

onMounted(() => {
    tarefasStore.getTarefas();
    colaboradoresStore.getColaboradores();
});
</script>

<template>
    <Toast />

    <ConfirmDialog />

    <ModalLoading />

    <main class="main">
        <GraficoGantt />

        <Colaboradores />

        <Maquinas />
    </main>
</template>

<style scoped>
.main {
    padding: 20px;
}
</style>

