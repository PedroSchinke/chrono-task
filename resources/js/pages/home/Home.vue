<script setup>
import { onMounted, ref } from 'vue';
import GraficoGantt from "./components/GraficoGantt.vue";
import Maquinas from "./components/Maquinas.vue";
import api from "@/axios.js";

const maquinas = ref([]);

const loadingMaquinas = ref(false);

onMounted(() => {
    getMaquinas();
});

const getMaquinas = async () => {
    loadingMaquinas.value = true;

    try {
        const resp = await api.get('/maquinas');

        maquinas.value = resp.data.data;
    } catch (e) {
        loadingMaquinas.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar máquinas', life: 3000 });
    }

    loadingMaquinas.value = false;
}
</script>

<template>
    <main class="main">
        <GraficoGantt :maquinas="maquinas" @recarregar-maquinas="getMaquinas()" />

        <Maquinas
            :maquinas="maquinas"
            :loading-maquinas="loadingMaquinas"
            @recarregar-maquinas="getMaquinas()"
        />
    </main>
</template>

<style scoped>
.main {
    padding: 20px;
}
</style>

