<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";
import Toast from "primevue/toast";
import GraficoGantt from "./components/GraficoGantt.vue";
import Maquinas from "./components/Maquinas.vue";
import Colaboradores from "@/pages/home/components/Colaboradores.vue";

const toast = useToast();

const maquinas = ref([]);
const horariosDisponiveis = ref([]);

const loadingMaquinas = ref(false);

onMounted(() => {
    getMaquinas();
    getHorariosDisponiveis();
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

const getHorariosDisponiveis = async () => {
    try {
        const resp = await api.get('/horarios-disponiveis');

        horariosDisponiveis.value = resp.data.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível buscar horários disponíveis', life: 3000 });
    }
}
</script>

<template>
    <Toast />

    <main class="main">
        <GraficoGantt
            :maquinas="maquinas"
            :horarios-disponiveis="horariosDisponiveis"
            @recarregar-maquinas="getMaquinas()"
        />

        <Colaboradores />

        <Maquinas
            :maquinas="maquinas"
            :loading-maquinas="loadingMaquinas"
            @recarregar-maquinas="getMaquinas()"
            @recarregar-horarios-disponiveis="getHorariosDisponiveis()"
        />
    </main>
</template>

<style scoped>
.main {
    padding: 20px;
}
</style>

