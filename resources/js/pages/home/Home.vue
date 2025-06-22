<script setup>
import GraficoGantt from "./components/GraficoGantt.vue";
import Maquinas from "./components/Maquinas.vue";

defineProps(['mensagem'])
</script>

<template>
    <main class="main">
        <GraficoGantt />

        <Maquinas />
    </main>
</template>

<style scoped>
.main {
    padding: 20px;
}
</style>

