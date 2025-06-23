<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";
import dayjs from "dayjs";
import Toast from "primevue/toast";
import Button from "primevue/button";
import AdicionarTarefa from "./AdicionarTarefa.vue";
import LinhaGantt from './LinhaGantt.vue';

const qtdDiasExibidos = ref(60);
const dias = getDates(dayjs(), dayjs().add(qtdDiasExibidos.value, 'day'));

function getDates(startDate, stopDate) {
    const dateArray = [];
    let currentDate = startDate;

    while (currentDate.isBefore(stopDate)) {
        dateArray.push(currentDate);
        currentDate = currentDate.add(1, 'day');
    }

    return dateArray;
}

const tarefas = ref([]);

const toast = useToast();

const adicionarTarefaDialog = ref(null);

onMounted(() => {
    getTarefas();
});

const getTarefas = async () => {
    try {
        const resp = await api.get('/tarefas');

        tarefas.value = resp.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível buscar tarefas', life: 3000 });
    }
}

const adicionarTarefa = () => {
    adicionarTarefaDialog.value.openDialog();
}
</script>

<template>
    <Toast />

    <AdicionarTarefa ref="adicionarTarefaDialog" @recarregar-tarefas="getTarefas()" />

    <header>
        <h1>Tarefas</h1>

        <Button title="Adicionar Tarefa" icon="pi pi-plus" rounded @click="adicionarTarefa()" />
    </header>

    <div class="gantt-chart">
        <div class="gantt-header" :style="`grid-template-columns: 200px repeat(${qtdDiasExibidos + 1}, 100px)`">
            <div class="task-label-header">Tarefa</div>
            <div v-for="dia in dias" :key="dia" class="day-header">
                {{ dia.format('DD/MM') }}
            </div>
        </div>

        <LinhaGantt
            v-for="tarefa in tarefas"
            :key="tarefa.id"
            :tarefa="tarefa"
            :dias="dias"
        />
    </div>
</template>

<style scoped>
header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.gantt-chart {
    overflow-x: auto;
    white-space: nowrap;
}

.gantt-header {
    min-width: fit-content;
    display: grid;
    font-weight: bold;
    background: #18181b;
    border-bottom: 1px solid #27272a;
}

.task-label-header {
    padding: 10px;
    border-right: 1px solid #27272a;
    background: #18181b;
}

.day-header {
    text-align: center;
    padding: 10px 0;
    border-left: 1px solid #27272a;
}
</style>

