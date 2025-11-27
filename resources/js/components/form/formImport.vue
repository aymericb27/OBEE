<template>
    <div class="container">
        <form class="bg-white border p-4 rounded text-center" @submit.prevent="sendImport">
            <h3 class="primary_color mb-5">Importation d'une unité d'enseignement</h3>

            <div class="mb-3">
                <label>Veuillez faire l'importation de donnée via le modèle fourni</label>
                <button class="btn btn-primary ml-2" type="button" @click="downloadModel">
                    télécharger le modèle
                </button>
            </div>

            <div>
                <label>Une fois rempli, vous pouvez importer le fichier</label>

                <input type="file" class="ml-2 form" @change="handleFileUpload" />
            </div>

            <div class="text-center mt-5">
                <button class="btn btn-secondary mr-2" type="button" @click="closeModalAAV">Annuler</button>
                <button class="btn btn-primary" type="submit">envoyer</button>
            </div>
        </form>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            fileToImport: null,
        };
    },

    methods: {
        handleFileUpload(event) {
            this.fileToImport = event.target.files[0];
        },

        async sendImport() {
            if (!this.fileToImport) {
                alert("Veuillez choisir un fichier");
                return;
            }

            let formData = new FormData();
            formData.append("file", this.fileToImport);

            try {
                const response = await axios.post("/import/post", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                });

                console.log("Import OK :", response.data);
            } catch (error) {
                console.error("Erreur import :", error);
            }
        },

        async downloadModel() {
            try {
                axios({
                    url: `/download/model_import`,
                    method: "GET",
                    responseType: "blob",
                }).then((response) => {
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement("a");
                    link.href = url;
                    link.setAttribute("download", "model_import.xlsx");
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                });
            } catch (error) {
                console.log(error);
            }
        },
    },
};
</script>
