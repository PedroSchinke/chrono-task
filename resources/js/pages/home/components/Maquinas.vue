<script setup>
import { ref } from "vue";
import Toast from 'primevue/toast';
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import AdicionarMaquina from "./AdicionarMaquina.vue";

const props = defineProps(['maquinas', 'loadingMaquinas']);
const emit = defineEmits(['recarregarMaquinas']);

const colunas = ref([
    { label: 'Segunda', name: 'segunda' },
    { label: 'Terça', name: 'terca' },
    { label: 'Quarta', name: 'quarta' },
    { label: 'Quinta', name: 'quinta' },
    { label: 'Sexta', name: 'sexta' },
    { label: 'Sabado', name: 'sabado' },
    { label: 'Domingo', name: 'domingo' },
]);

const adicionarMaquinaDialog = ref(null);

const exibirHorariosDisponiveis = (diaSemana, maquina, chave) => {
    if (!maquina.horarios_disponiveis) return '';

    const horario = maquina.horarios_disponiveis.find(h => h.dia_semana === diaSemana);
    return horario ? horario[chave].substring(0, 5) : '';
}

const adicionarMaquina = () => {
    adicionarMaquinaDialog.value.openDialog();
}

const recarregarMaquinas = () => {
    emit('recarregarMaquinas');
}
</script>

<template>
    <Toast />

    <AdicionarMaquina ref="adicionarMaquinaDialog" @recarregar-maquinas="recarregarMaquinas()" />

    <main>
        <header>
            <h1>Máquinas</h1>

            <Button title="Adicionar Máquina" icon="pi pi-plus" rounded @click="adicionarMaquina()" />
        </header>

        <DataTable :value="maquinas" :loading="loadingMaquinas" >
            <Column header="Máquina" field="nome" />

            <Column v-for="coluna of colunas" :header="coluna.label">
                <template #body="{ data }">
                    {{ exibirHorariosDisponiveis(coluna.name, data, 'hora_inicio') }}
                    -
                    {{ exibirHorariosDisponiveis(coluna.name, data, 'hora_fim') }}
                </template>
            </Column>
        </DataTable>
    </main>
</template>

<style scoped>
main {
    display: flex;
    flex-direction: column;
}

header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>
