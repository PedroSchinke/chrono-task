<script setup>
import Toast from "primevue/toast";
import TarefaGantt from "@/pages/home/components/TarefaGantt.vue";

const props = defineProps(['maquina', 'dias', 'tamanhoDia', 'horariosDisponiveis']);
const emit = defineEmits(['reposicionar', 'recarregar']);

const reposicionarTarefa = (data) => {
    emit('reposicionar', data);
}

const recarregar = () => {
    emit('recarregar');
}
</script>

<template>
    <Toast />

    <div class="gantt-row" :id="`linha-maquina-${maquina.id}`">
        <div class="task-label">{{ maquina.nome }}</div>

        <div class="row-content">
            <TarefaGantt
                v-for="tarefa in props.maquina.tarefas"
                :tarefa="tarefa"
                :dias="props.dias"
                :tamanho-dia="props.tamanhoDia"
                :maquina="props.maquina"
                :horarios-disponiveis="props.horariosDisponiveis"
                @reposicionar="(data) => reposicionarTarefa(data)"
                @recarregar="recarregar()"
            />
        </div>
    </div>

    <hr class="divisor-linhas">
</template>

<style scoped>
.gantt-row {
    position: relative;
    display: flex;
    min-height: 60px;
}

.task-label {
    width: 200px;
    padding: 10px;
    display: flex;
    align-items: center;
    background: #18181b;
    border-right: 1px solid #27272a;
    border-bottom: 1px solid #27272a;
    white-space: break-spaces;
}

.row-content {
    position: relative;
    flex: 1;
}

.divisor-linhas {
    position: absolute;
    right: 30px;
    left: 28px;
    margin: 0;
    height: 1px;
    background: rgb(39, 39, 42);
    border: none;
}
</style>
