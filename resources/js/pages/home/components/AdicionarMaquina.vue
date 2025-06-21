<script setup>
import { reactive, ref } from "vue";
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";

const dialogVisible = ref(false);

const nome = ref('');

const toast = useToast();

const adicionarMaquina = async () => {
    try {
        validaNome()
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Alerta', detail: e.message, life: 3000 });

        return;
    }

    try {
        const resp = await axios.post('maquina', { nome: nome.value });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar máquina', life: 3000 });

        return;
    }

    toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Máquina adicionada com sucesso', life: 3000 });

    closeDialog();
}

const validaNome = () => {
    if (nome.value.trim() === '') {
        throw new Error('É preciso inserir o nome da máquina');
    }
}

const openDialog = () => {
    dialogVisible.value = true;
}

const closeDialog = () => {
    dialogVisible.value = false;
}

defineExpose({
   openDialog,
   closeDialog
});
</script>

<template>
    <Toast />

    <Dialog header="Adicionar Máquina" v-model:visible="dialogVisible">
        <div class="dialog-content">
            <InputText placeholder="Nome da máquina" v-model="nome"/>

            <Button label="Salvar" icon="pi pi-check" @click="adicionarMaquina()" />
        </div>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>
