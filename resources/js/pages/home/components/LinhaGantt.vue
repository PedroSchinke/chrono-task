<script setup>
import TarefaGantt from "@/pages/home/components/TarefaGantt.vue";

const props = defineProps(['tarefa', 'dias', 'larguraDiaPx', 'alturaTarefaPx', 'horasExibidas', 'horariosDisponiveis']);
const emit = defineEmits(['reposicionar']);

const reposicionarTarefa = (data) => {
    emit('reposicionar', data);
}
</script>

<template>
    <div
        class="gantt-row"
        :id="`linha-tarefa-${tarefa.id}`"
        :style="`height: ${props.alturaTarefaPx}px; max-height: ${props.alturaTarefaPx}px;`"
    >
        <div class="task-label">{{ tarefa.titulo }}</div>

        <div class="row-content">
            <TarefaGantt
                :tarefa="props.tarefa"
                :dias="props.dias"
                :largura-dia-px="props.larguraDiaPx"
                :altura-tarefa-px="props.alturaTarefaPx"
                :horas-exibidas="props.horasExibidas"
                :horarios-disponiveis="props.horariosDisponiveis"
                @reposicionar="(data) => reposicionarTarefa(data)"
            />
        </div>
    </div>

    <hr class="divisor-linhas">
</template>

<style scoped>
.gantt-row {
    position: relative;
    display: flex;
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
