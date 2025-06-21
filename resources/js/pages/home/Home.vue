<script setup>
import GanttChart from "./components/GanttChart.vue";
import Machines from "./components/Machines.vue";

defineProps(['mensagem'])
</script>

<template>
    <main class="main">
        <GanttChart />

        <Machines />
    </main>
</template>

<style scoped>
.main {
    padding: 20px;
}
</style>

