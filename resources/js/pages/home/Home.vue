<script setup>
import Toast from "primevue/toast";
import GraficoGantt from "./components/GraficoGantt.vue";
import Maquinas from "./components/Maquinas.vue";
import Colaboradores from "@/pages/home/components/Colaboradores.vue";

</script>

<template>
    <Toast />

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

