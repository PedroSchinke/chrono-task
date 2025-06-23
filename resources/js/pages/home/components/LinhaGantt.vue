<script setup>
import { computed } from 'vue';

const props = defineProps(['tarefa', 'dias']);

const diasEntre = (inicio, fim) => {
    const oneDay = 1000 * 60 * 60 * 24
    return Math.round((new Date(fim) - new Date(inicio)) / oneDay)
}

const blocoStyle = computed(() => {
    const totalDias = props.dias.length;
    const inicio = diasEntre(props.dias[0], props.tarefa.inicio);
    const duracao = diasEntre(props.tarefa.inicio, props.tarefa.fim);

    return {
        left: `${inicio * 100}px`,
        width: `${(duracao + 1) * 100}px`,
        backgroundColor: props.tarefa.cor
    }
})
</script>

<template>
    <div class="gantt-row">
        <div class="task-label">{{ tarefa.titulo }}</div>

        <div class="row-content">
            <div class="tarefa-bloco" :style="blocoStyle">
                <div class="bloco-texto">
                    {{ tarefa.maquina.nome }}
                </div>
            </div>
        </div>
    </div>

    <hr class="divisor-linhas">
</template>

<style scoped>
.gantt-row {
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
    white-space: break-spaces;
}

.row-content {
    position: relative;
    flex: 1;
}

.tarefa-bloco {
    position: absolute;
    height: 100%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    font-size: 0.9rem;
    padding: 0 5px;
}

.bloco-texto {
    white-space: nowrap;
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
