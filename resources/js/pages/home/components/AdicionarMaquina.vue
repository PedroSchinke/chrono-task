<script setup>
import { ref } from "vue";
import { useToast } from 'primevue/usetoast';
import api from '@/axios';
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import ModalLoading from "@/components/ModalLoading.vue";

const emits = defineEmits(['recarregar-maquinas']);

const dialogVisible = ref(false);

const nome = ref('');

const loading = ref(false);
const loadingMessage = ref('Adicionando Máquina...');

const toast = useToast();

const adicionarMaquina = async () => {
    try {
        validaNome()
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Alerta', detail: e.message, life: 3000 });

        return;
    }

    loading.value = true;

    try {
        const resp = await api.post('/maquina', { nome: nome.value });

        loading.value = false;

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Máquina adicionada com sucesso', life: 3000 });

        emits('recarregar-maquinas');

        closeDialog();
    } catch (e) {
        loading.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar máquina', life: 3000 });
    }
}

const validaNome = () => {
    if (nome.value.trim() === '') {
        throw new Error('É preciso inserir o nome da máquina');
    }
}

const openDialog = () => {
    nome.value = '';

    dialogVisible.value = true;
}

const closeDialog = () => {
    nome.value = '';

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <ModalLoading :is-loading="loading" :message="loadingMessage" />

    <Dialog header="Adicionar Máquina" v-model:visible="dialogVisible">
        <form class="dialog-content">
            <InputText placeholder="Nome" v-model="nome"/>

            <Button label="Salvar" icon="pi pi-check" @click="adicionarMaquina()" />
        </form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>
