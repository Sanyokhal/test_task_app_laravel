<script>
import {defineComponent} from 'vue'
import axios from "axios";

export default defineComponent({
  name: "uploadFile",
  data() {
    return {
      selectedFile: null,
      upload_data: null,
      errors: null,
      loading: false
    }
  },
  methods: {
    clearSelection() {
      this.selectedFile = null;
      this.$refs.fileInput.value = null;
    },
    uploadFile() {
      this.loading = true;
      if (this.selectedFile == null) {
        alert("Файл не вибрано!")
        this.loading = false;
        return;
      }
      var formData = new FormData();
      formData.append('file', this.selectedFile)
      axios.post('http://127.0.0.1:8000/api/upload_price', formData).then(response => {
        this.upload_data = response.data;
        this.loading = false;
        this.errors = null;
      }).catch(error => {
        alert("Помилка")
        this.errors = error.response.data.error
        this.upload_data = null;
        this.loading = false;
      });
    },
    onFileChanged($event) {
      this.selectedFile = $event.target.files[0];
      console.log(this.selectedFile);
    }
  }
})
</script>

<template>
  <div>
    <input type="file" ref="fileInput" @change="onFileChanged($event)"
           accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
    <br>
    <button @click="uploadFile">Надіслати</button>
    <button @click="clearSelection">Очистити вибір</button>
    <br>
    <h2 v-if="loading">!! Грузиться файл</h2>
    <h3 v-if="!loading && upload_data != null">Дані про загрузку
      <ul>
        <li>Погані артикули - {{ upload_data.bad_articles }}</li>
        <li>Норм артикули - {{ upload_data.written_qty }}</li>
        <li>Дублікати - {{ upload_data.duplicates }}</li>
        <li>Загалом - {{ upload_data.overall_qty }}</li>
        <li>Назва локального файла була - {{ upload_data.file }}</li>
      </ul>
    </h3>
    <h3 v-if="!loading && errors != null">
      <ul>
        <li v-for="error in errors">
          {{ error }}
        </li>
      </ul>
    </h3>
  </div>
</template>

<style scoped>

</style>
