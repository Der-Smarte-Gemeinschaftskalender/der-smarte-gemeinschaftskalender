<script setup lang="ts">
import { useRouter } from "vue-router";
import { localLogout } from "@/composables/UserComposoable";
import Button from "./KERN/Button.vue";

const router = useRouter();

const logout = () => {
  try {
    localLogout();
    router.push("/");
  } catch (error) {
    console.error(error);
  }
};
</script>
<template>
    <Button type="tertiary" icon-left="logout" @click="logout"> Abmelden </Button>
</template>
