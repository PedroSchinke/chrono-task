<script setup>
import { reactive, ref } from "vue";
import { useToast } from 'primevue/usetoast';
import * as z from 'zod';
import Dialog from "primevue/dialog";
import { Form } from "@primevue/forms";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import InputMask from "primevue/inputmask";
import IftaLabel from "primevue/iftalabel";
import Button from "primevue/button";
import api from "@/axios.js";
import Toast from "primevue/toast";
import { FormularioNaoPreenchidoError } from "@/errors/FormularioNaoPreenchidoError.js";
import ModalLoading from "@/components/ModalLoading.vue";

const emits = defineEmits(['recarregar-colaboradores']);

const dialogVisible = ref(false);

const loading = ref(false);
const loadingMessage = ref('Adicionando Colaborador...');

const form = reactive({
    primeiro_nome: '',
    sobrenome: '',
    cpf: '',
    email: ''
});

const resolver = z.object({
    primeiro_nome: z.string().min(1, { message: 'Primeiro nome é obrigatório.' }),
    sobrenome: z.string().min(1, { message: 'Sobrenome é obrigatório.' }),
    cpf: z.string().min(1, { message: 'CPF é obrigatório.' }),
    email: z.email({ message: 'Email inválido.' })
});

const toast = useToast();

const adicionarColaborador = async (values) => {
    try {
        validaForm();

        loading.value = true;

        const params = {
            primeiro_nome: form.primeiro_nome,
            sobrenome: form.sobrenome,
            cpf: form.cpf,
            email: form.email
        }

        await api.post('/colaboradores', params);
    } catch (e) {
        if (e instanceof FormularioNaoPreenchidoError) {
            toast.add({ severity: 'error', text: 'Não foi possível adicionar máquina', life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar máquina', life: 3000 });
        }

        loading.value = false;

        return;
    }

    loading.value = false;

    toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Colaborador adicionado com sucesso', life: 3000 });

    emits('recarregar-colaboradores');

    closeDialog();
}

const validaForm = () => {
    Object.values(form).forEach((field) => {
       if (field.trim() === '') {
           throw new FormularioNaoPreenchidoError(
               'Erro',
               'Preencha todos os campos para adicionar o colaborador.'
           );
       }
    });
}

const resetForm = () => {
    form.primeiro_nome = '';
    form.sobrenome = '';
    form.cpf = '';
    form.email = '';
}

const openDialog = () => {
    resetForm();

    dialogVisible.value = true;
}

const closeDialog = () => {
    resetForm();

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <Toast />

    <ModalLoading :is-loading="loading" :message="loadingMessage" />

    <Dialog header="Adicionar Colaborador" v-model:visible="dialogVisible">
        <Form
            v-slot="$form"
            :schema="resolver"
            class="dialog-content"
            @submit.prevent="adicionarColaborador"
        >
            <IftaLabel>
                <label for="primeiro-nome">Primeiro Nome</label>
                <InputText id="primeiro-nome" name="primeiro_nome" v-model="form.primeiro_nome" />
                <Message v-if="$form.primeiro_nome?.invalid" severity="error" size="small" variant="simple">
                    {{ $form.primeiro_nome.error?.message }}
                </Message>
            </IftaLabel>

            <IftaLabel>
                <label for="sobrenome">Sobrenome</label>
                <InputText id="sobrenome" name="sobrenome" v-model="form.sobrenome" />
                <Message v-if="$form.sobrenome?.invalid" severity="error" size="small" variant="simple">
                    {{ $form.sobrenome.error?.message }}
                </Message>
            </IftaLabel>

            <IftaLabel>
                <label for="cpf">CPF</label>
                <InputMask id="cpf" name="cpf" mask="999.999.999-99" v-model="form.cpf" fluid />
                <Message v-if="$form.cpf?.invalid" severity="error" size="small" variant="simple">
                    {{ $form.cpf.error?.message }}
                </Message>
            </IftaLabel>

            <IftaLabel>
                <label for="email">Email</label>
                <InputText id="email" name="email" type="text" placeholder="Email" v-model="form.email" fluid />
                <Message v-if="$form.email?.invalid" severity="error" size="small" variant="simple">
                    {{ $form.email.error?.message }}
                </Message>
            </IftaLabel>

            <Button label="Salvar" icon="pi pi-check" type="submit" />
        </Form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>
