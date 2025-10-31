<template>
    <div class="p-4 container mt-4 bg-light border">
        <p>Voulez vous extraire :</p>
        <ul>
            <li class="mb-2">
                <div>
                    <input
                        type="checkbox"
                        class="form-check-input"
                        v-model="prog.all"
                    />
                    Les programmes
                    <ul>
                        <li>
                            <input
                                type="checkbox"
                                class="form-check-input"
                                v-model="prog.code"
                            />
                            Code
                        </li>
                        <li>
                            <input
                                type="checkbox"
                                class="form-check-input"
                                v-model="prog.name"
                            />
                            Nom
                        </li>
                    </ul>
                </div>
            </li>
            <li class="mb-2">
                <div>
                    <input type="checkbox" class="form-check-input" /> Les
                    unités d'enseignements
                </div>
                <ul>
                    <li>
                        <input type="checkbox" class="form-check-input" /> Code
                    </li>
                    <li>
                        <input type="checkbox" class="form-check-input" /> Nom
                    </li>
                    <li>
                        <input type="checkbox" class="form-check-input" /> ECTS
                    </li>
                    <li>
                        <input type="checkbox" class="form-check-input" />
                        Description
                    </li>
                    <li>
                        <input type="checkbox" class="form-check-input" /> date
                        de début
                    </li>
                    <li>
                        <input type="checkbox" class="form-check-input" /> date
                        de fin
                    </li>
                </ul>
            </li>
            <li class="mb-2">
                <div>
                    <input type="checkbox" class="form-check-input" /> Les
                    acquis d'apprentissages visés
                    <ul>
                        <li>
                            <input type="checkbox" class="form-check-input" />
                            Code
                        </li>
                        <li>
                            <input type="checkbox" class="form-check-input" />
                            Nom
                        </li>
                        <li>
                            <input type="checkbox" class="form-check-input" />
                            Description
                        </li>
                    </ul>
                </div>
            </li>
            <li>
                <div>
                    <input type="checkbox" class="form-check-input" /> Les
                    prérequis
                    <ul>
                        <li>
                            <input type="checkbox" class="form-check-input" />
                            Code
                        </li>
                        <li>
                            <input type="checkbox" class="form-check-input" />
                            Nom
                        </li>
                        <li>
                            <input type="checkbox" class="form-check-input" />
                            Description
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <button type="button" @click="exportData()" class="btn btn-primary">
            Exporter le fichier
        </button>
    </div>
</template>
<script>
import axios from "axios";

export default {
    data() {
        return {
            prog: {
                all: false,
            },
            reloadKey: 0, // ✅ Clé réactive pour forcer le rechargement
        };
    },
    methods: {
        async exportData() {
            const response = await axios
                .get("/export/get", { responseType: "blob" })
                .then((response) => {
                    const url = window.URL.createObjectURL(
                        new Blob([response.data])
                    );
                    const link = document.createElement("a");
                    link.href = url;
                    link.setAttribute("download", "programme.xlsx");
                    document.body.appendChild(link);
                    link.click();
                });
        },
    },
};
</script>
