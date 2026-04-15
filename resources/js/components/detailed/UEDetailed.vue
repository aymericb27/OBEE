<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left primary_color"></i>
                Retour
            </a>
        </div>
        <div class="container pb-4">
            <div v-if="$route.query.message" class="alert alert-success mt-3">
                <i
                    class="fa-solid fa-check green mr-2"
                    style="color: darkgreen"
                ></i>
                <span> {{ $route.query.message }} </span>
            </div>
            <div class="p-4 border rounded bg-white mt-3 shadow">
                <div class="row mb-2">
                    <h3 class="primary_color col-md-10 mb-0">
                        <span class="box_code UE pl-2 pr-2 mr-2">{{
                            ue.code
                        }}</span>
                        <span>
                            {{ ue.name }}
                        </span>
                        <AnomalyBadge
                            class="ml-2"
                            :summary="displayedAnomalySummary"
                        />
                    </h3>
                    <span class="col-md-2 text-right">
                        <i
                            style="font-size: 24px"
                            class="fa-regular fa-trash-can mr-2 deleteBtn"
                            title="supprimer"
                            @click="openModalDelete = true"
                        ></i>
                        <router-link
                            :to="{
                                name: 'modifyUE',
                            }"
                        >
                            <i
                                style="font-size: 28px"
                                class="fa-regular fa-pen-to-square primary_color"
                                title="éditer"
                            ></i>
                        </router-link>
                        <i
                            @click="exportUE(ue.id)"
                            style="font-size: 28px"
                            class="fa-solid ml-2 fa-download green_color cursor_pointer"
                            title="télécharger"
                        ></i>
                    </span>
                </div>
                <span> </span>
                <div class="pb-4 border-bottom mb-4">
                    <div v-html="ue.description"></div>
                    <div v-if="ue.parent && ue.parent.length">
                        Cette unité est un élément constitutif de
                        <strong>
                            <router-link
                                class="UE"
                                v-if="ue.parent[0].id"
                                :to="{
                                    name: 'ue-detail',
                                    params: { id: ue.parent[0].id },
                                }"
                            >
                                {{ ue.parent[0].code }}
                            </router-link>
                            {{ ue.parent[0].name }}
                        </strong>
                    </div>
                </div>

                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('anomalies')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Anomalies détectées
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('anomalies')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <div
                        v-show="isExpanded('anomalies')"
                        class="border rounded p-3 bg-light"
                    >
                        <div
                            v-if="groupedAnomalies.length"
                            v-for="(anom, anomIndex) in groupedAnomalies"
                            :key="anom.key"
                            :class="[
                                'mb-2',
                                'pb-2',
                                anomIndex < groupedAnomalies.length - 1
                                    ? 'border-bottom'
                                    : '',
                            ]"
                        >
                            <div class="d-flex align-items-center">
                                <AnomalyBadge
                                    :summary="{
                                        has_anomaly: true,
                                        count: 1,
                                        severity: anom.severity || 'warning',
                                    }"
                                    :showCount="false"
                                />
                                <strong class="ml-2">{{
                                    anomalyTypeText(anom)
                                }}</strong>
                            </div>
                            <div class="text-muted small mt-1">
                                {{ anomalyActionText(anom) }}
                            </div>
                            <div class="mt-1">
                                <ul class="mb-0 anomaly-bullet-list">
                                    <li
                                        v-for="item in anomalyListItems(anom)"
                                        :key="item.key"
                                    >
                                        <template v-if="item.text">
                                            {{ item.text }}
                                        </template>
                                        <template v-else>
                                            <span v-if="item.prefix">{{
                                                item.prefix
                                            }}</span>
                                            <router-link
                                                v-if="item.routeName && item.id"
                                                :to="{
                                                    name: item.routeName,
                                                    params: { id: item.id },
                                                }"
                                            >
                                                <span
                                                    style="font-size: 1.1em"
                                                    :class="item.codeClass"
                                                >
                                                    {{ item.codeText }}
                                                </span>
                                            </router-link>
                                            <span
                                                v-else
                                                :class="item.codeClass"
                                            >
                                                {{ item.codeText }}
                                            </span>
                                            <span v-if="item.labelText">{{
                                                item.labelText
                                            }}</span>
                                            <span v-if="item.suffix">{{
                                                item.suffix
                                            }}</span>
                                        </template>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div v-else class="text-muted">
                            Aucune anomalie détectée pour cette UE.
                        </div>
                    </div>
                </div>

                <div
                    class="listComponent mb-4"
                    v-if="ue.children && ue.children.length"
                >
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('children')"
                    >
                        <h5 class="d-inline-block primary_color">
                            liste des éléments constitutifs
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('children')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        v-show="isExpanded('children')"
                        routeGET="/ue/ecs/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="ue-detail"
                        typeList="UE"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('programs')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-scroll"></i> Faisant partie
                            du/des programme(s)
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('programs')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        v-show="isExpanded('programs')"
                        routeGET="/ue/pro/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="pro-detail"
                        typeList="PRO"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('aavVise')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-brands fa-google-scholar"></i>
                            Liste des acquis d'apprentissage visé par l'UE (AAV)
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('aavVise')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        v-show="isExpanded('aavVise')"
                        routeGET="/ue/aavvise/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="[
                            'code',
                            'name',
                            'aat_contributions',
                            ...(ue.children && ue.children.length
                                ? ['element_constitutif_aav']
                                : []),
                        ]"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('prerequis')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-key"></i>
                            Liste des prérequis en termes d'AAV
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('prerequis')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        v-show="isExpanded('prerequis')"
                        routeGET="/ue/aavprerequis/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="PRE"
                        :listColonne="['code', 'name', 'aat_contributions']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('prerequisUE')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-key"></i>
                            Liste des prérequis en termes d'UE
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('prerequisUE')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}-ue-prerequis`"
                        v-if="ue.id"
                        v-show="isExpanded('prerequisUE')"
                        routeGET="/ue/ueprerequis/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="ue-detail"
                        typeList="UE"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('aat')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-graduation-cap"></i>
                            Liste des acquis d'apprentissage terminaux auxquels
                            l'UE affirme contribuer
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('aat')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        v-show="isExpanded('aat')"
                        routeGET="/ue/aat/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aat-detail"
                        typeList="AAT"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div
                        class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                        @click="toggleSection('aatFromAav')"
                    >
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-graduation-cap"></i>
                            Liste des acquis d'apprentissage terminaux
                            auxquelles les AAV de l'UE contribuent
                        </h5>
                        <i
                            class="fa-solid primary_color"
                            :class="
                                isExpanded('aatFromAav')
                                    ? 'fa-chevron-down'
                                    : 'fa-chevron-up'
                            "
                        ></i>
                    </div>
                    <list
                        :key="`${ue.id}-aat-from-aav`"
                        v-if="ue.id"
                        v-show="isExpanded('aatFromAav')"
                        routeGET="/ue/aat/from-aav/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aat-detail"
                        typeList="AAT"
                        :listColonne="['code', 'name']"
                    />
                </div>
            </div>
        </div>
    </div>

    <ConfirmDeleteModal
        :show="openModalDelete"
        :name="ue.name"
        type="UE"
        :idToDelete="ue.id"
        @confirm="deleteItem"
        @cancel="openModalDelete = false"
    />
</template>

<script>
import axios from "axios";
import list from "../list.vue";
import dayjs from "dayjs";
import { onMounted, watch } from "vue";

import errorShedule from "../error/ErrUEShedule.vue";
import ConfirmDeleteModal from "../modal/confirmDeleteModal.vue";
import AnomalyBadge from "../common/AnomalyBadge.vue";

export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { list, errorShedule, ConfirmDeleteModal, AnomalyBadge },
    computed: {
        displayedAnomalySummary() {
            const groupedCount = this.groupedAnomalies.length;
            const base = this.ue?.anomaly_summary || {};
            const groupedSeverity = this.groupedAnomalies.reduce(
                (acc, item) => {
                    const weight = { info: 1, warning: 2, error: 3 };
                    const current = item?.severity || "warning";
                    return (weight[current] || 1) > (weight[acc] || 1)
                        ? current
                        : acc;
                },
                "info",
            );
            return {
                has_anomaly: groupedCount > 0,
                count: groupedCount,
                severity:
                    groupedCount > 0
                        ? groupedSeverity
                        : base.severity || "warning",
            };
        },
        groupedAnomalies() {
            const anomalies = Array.isArray(this.ue?.anomalies)
                ? this.ue.anomalies
                : [];
            const map = new Map();

            anomalies.forEach((anom) => {
                if (!anom || anom.code === "UE_ANOM_01") return;
                const key = `${anom.code || ""}|${anom.message || ""}`;
                if (!map.has(key)) {
                    map.set(key, {
                        key,
                        code: anom.code,
                        message: anom.message,
                        count: 1,
                        severity: anom.severity || "warning",
                        details: anom.details || {},
                        entries: [anom],
                    });
                    return;
                }

                const current = map.get(key);
                current.count += 1;
                current.entries.push(anom);
                if (!current.details || !Object.keys(current.details).length) {
                    current.details = anom.details || {};
                }
                if (
                    (anom.severity === "error" &&
                        current.severity !== "error") ||
                    (anom.severity === "warning" && current.severity === "info")
                ) {
                    current.severity = anom.severity;
                }
            });

            return Array.from(map.values());
        },
    },
    data() {
        return {
            openModalDelete: false,
            selectedEC: false,
            ue: {
                name: "",
                description: "",
                code: "",
                semestre: "",
                aavs: {},
                ecs: {},
            },
            errors: {
                errorsECTS: [],
                errorsShedule: [],
                isError: false,
            },
            expandedSections: {
                anomalies: true,
                children: true,
                programs: true,
                aavVise: true,
                prerequis: true,
                prerequisUE: true,
                aat: true,
                aatFromAav: true,
            },
        };
    },
    methods: {
        routeNameByType(type) {
            if (type === "UE") return "ue-detail";
            if (type === "PRO") return "pro-detail";
            if (type === "AAV") return "aav-detail";
            if (type === "AAT") return "aat-detail";
            return null;
        },
        anomalyListItems(anom) {
            const code = anom?.code || "";
            const details = anom?.details || {};
            const entries = Array.isArray(anom?.entries) ? anom.entries : [];
            const items = [];

            const pushLinked = (
                key,
                type,
                id,
                codeText,
                labelText = "",
                prefix = "",
                suffix = "",
            ) => {
                items.push({
                    key,
                    routeName: this.routeNameByType(type),
                    id: id ?? null,
                    codeClass: type,
                    codeText: codeText || "-",
                    labelText,
                    prefix,
                    suffix,
                });
            };

            if (code === "UE_ANOM_03") {
                const prereqs = Array.isArray(details?.impacted_prereqs)
                    ? details.impacted_prereqs
                    : [];
                prereqs.forEach((p, idx) => {
                    pushLinked(
                        `pre-${idx}-${p?.prereq_id ?? idx}`,
                        "AAV",
                        p?.prereq_id ?? null,
                        p?.prereq_code || `AAV#${p?.prereq_id ?? "?"}`,
                        p?.prereq_name ? ` - ${p.prereq_name}` : "",
                    );
                });
            } else if (code === "UE_ANOM_12") {
                const prereqUes = Array.isArray(details?.impacted_ue_prereqs)
                    ? details.impacted_ue_prereqs
                    : [];
                prereqUes.forEach((u, idx) => {
                    pushLinked(
                        `ue-pre-sem-${idx}-${u?.prereq_ue_id ?? idx}`,
                        "UE",
                        u?.prereq_ue_id ?? null,
                        u?.prereq_ue_code || `UE#${u?.prereq_ue_id ?? "?"}`,
                        u?.prereq_ue_name ? ` - ${u.prereq_ue_name}` : "",
                    );
                });
            } else if (code === "UE_ANOM_10") {
                const invalidAavs = Array.isArray(
                    details?.invalid_aav_prerequis,
                )
                    ? details.invalid_aav_prerequis
                    : [];
                invalidAavs.forEach((a, idx) => {
                    pushLinked(
                        `pre-ue-mismatch-${idx}-${a?.aav_id ?? idx}`,
                        "AAV",
                        a?.aav_id ?? null,
                        a?.aav_code || `AAV#${a?.aav_id ?? "?"}`,
                        a?.aav_name ? ` - ${a.aav_name}` : "",
                    );
                });
                const missingUes = Array.isArray(details?.missing_ue_prerequis)
                    ? details.missing_ue_prerequis
                    : [];
                missingUes.forEach((u, idx) => {
                    pushLinked(
                        `pre-ue-uncovered-${idx}-${u?.id ?? idx}`,
                        "UE",
                        u?.id ?? null,
                        u?.code || `UE#${u?.id ?? "?"}`,
                        u?.name ? ` - ${u.name}` : "",
                        "UE prérequise sans AAV associé: ",
                    );
                });
            } else if (code === "UE_ANOM_07") {
                const missingAavs = Array.isArray(details?.missing_aavs)
                    ? details.missing_aavs
                    : [];
                missingAavs.forEach((a, idx) => {
                    pushLinked(
                        `aav-miss-${idx}-${a?.aav_id ?? idx}`,
                        "AAV",
                        a?.aav_id ?? null,
                        a?.aav_code || `AAV#${a?.aav_id ?? "?"}`,
                        a?.aav_name ? ` - ${a.aav_name}` : "",
                    );
                });
            } else if (code === "UE_ANOM_06") {
                const semesters = Array.isArray(details?.impacted_semesters)
                    ? details.impacted_semesters
                    : [];
                semesters.forEach((s, idx) => {
                    const suffix = s?.semester_number
                        ? ` (S${s.semester_number})`
                        : "";
                    pushLinked(
                        `sem-miss-${idx}-${s?.program_id ?? idx}`,
                        "PRO",
                        s?.program_id ?? null,
                        s?.program_code || `PRO#${s?.program_id ?? "?"}`,
                        s?.program_name ? ` - ${s.program_name}` : "",
                        "",
                        suffix,
                    );
                });
            } else if (code === "UE_ANOM_09") {
                const allCases = []
                    .concat(
                        Array.isArray(details?.declared_ue_but_no_aav_cases)
                            ? details.declared_ue_but_no_aav_cases
                            : [],
                    )
                    .concat(
                        Array.isArray(
                            details?.aav_to_aat_not_declared_in_ue_cases,
                        )
                            ? details.aav_to_aat_not_declared_in_ue_cases
                            : [],
                    );
                const seen = new Set();
                allCases.forEach((c, idx) => {
                    const aatId = c?.aat_id ?? null;
                    const key = String(aatId ?? `x-${idx}`);
                    if (seen.has(key)) return;
                    seen.add(key);
                    pushLinked(
                        `inc-aat-${key}`,
                        "AAT",
                        aatId,
                        c?.aat_code || `AAT#${aatId ?? "?"}`,
                        c?.aat_name ? ` - ${c.aat_name}` : "",
                    );
                });
            } else if (code === "UE_ANOM_08") {
                const detailsEntries = entries.length ? entries : [anom];
                detailsEntries.forEach((entry, idx) => {
                    const d = entry?.details || {};
                    if (!d?.aav_id && !d?.aat_id) return;
                    items.push({
                        key: `lvl-${idx}`,
                        text: `AAV ${d?.aav_code || d?.aav_id || "?"} -> AAT ${d?.aat_id || "?"}`,
                    });
                });
            } else if (code === "UE_ANOM_02") {
                const detailsEntries = entries.length ? entries : [anom];
                const seen = new Set();
                detailsEntries.forEach((entry, idx) => {
                    const d = entry?.details || {};
                    const ues = Array.isArray(d?.ue_prerequis)
                        ? d.ue_prerequis
                        : [];
                    if (!ues.length) return;
                    ues.forEach((u, uidx) => {
                        const key = String(u?.id ?? `${u?.code ?? ""}-${uidx}`);
                        if (seen.has(key)) return;
                        seen.add(key);
                        pushLinked(
                            `ue-pre-${idx}-${uidx}-${u?.id ?? uidx}`,
                            "UE",
                            u?.id ?? null,
                            u?.code || `UE#${u?.id ?? "?"}`,
                            u?.name ? ` - ${u.name}` : "",
                            "Prérequis UE: ",
                        );
                    });
                });
            }

            if (!items.length) {
                items.push({
                    key: `fallback-${code || "anom"}`,
                    text: anom?.message || "Anomalie detectee.",
                });
            }

            return items;
        },
        anomalyTypeText(anom) {
            const code = anom?.code || "";
            if (code === "UE_ANOM_02")
                return "Erreur de prérequis UE (AAV manquant)";
            if (code === "UE_ANOM_03") return "Erreur de prérequis AAV (semestre)";
            if (code === "UE_ANOM_12")
                return "Erreur de prerequis UE (semestre)";
            if (code === "UE_ANOM_10")
                return "Erreur de cohérence prérequis UE/AAV";
            if (code === "UE_ANOM_11")
                return "Erreur d'affectation au programme";
            if (code === "UE_ANOM_04")
                return "Erreur de données (liste des AAV vide)";
            if (code === "UE_ANOM_05")
                return "Erreur de données (crédits manquants)";
            if (code === "UE_ANOM_06")
                return "Erreur d'affectation de semestre";
            if (code === "UE_ANOM_07") return "Erreur de contribution";
            if (code === "UE_ANOM_08")
                return "Erreur de niveau de contribution";
            if (code === "UE_ANOM_09")
                return "Erreur de cohérence de contribution";
            return "Anomalie";
        },
        anomalyActionText(anom) {
            const code = anom?.code || "";
            if (code === "UE_ANOM_04")
                return "Ajouter des AAV visés à cette UE.";
            if (code === "UE_ANOM_05")
                return "Renseigner les crédits (ECTS) de cette UE.";
            if (code === "UE_ANOM_06")
                return "Certains semestres sont sans UE. vérifier l'affectation de semestre dans le(s) programme(s).";
            if (code === "UE_ANOM_07")
                return "Compléter les contributions AAV -> AAT manquantes.";
            if (code === "UE_ANOM_08")
                return "Renseigner le niveau de contribution du AAT concerné.";
            if (code === "UE_ANOM_09")
                return "L'UE declare contribuer a un AAT mais aucun AAV de l'UE ne contribue a cet AAT pour ce programme. Aligner la matrice UE -> AAT avec les contributions des AAV.";
            if (code === "UE_ANOM_03")
                return "Un ou des prérequis AAV ne fait pas partie des acquis d'apprentissages visées des semestres precedents. Vérifier que le prérequis appartient aux AAV autorisés.";
            if (code === "UE_ANOM_12")
                return "Un ou des prérequis UE ne fait pas partie des UE des semestres précédents. Vérifier que chaque UE prérequise est placée dans un semestre antérieur.";
            if (code === "UE_ANOM_10")
                return "Vérifier que chaque AAV prérequis appartient aux UE prérequises et que chaque UE prérequise est couverte par au moins un AAV prérequis.";
            if (code === "UE_ANOM_11")
                return "Associer cette UE à au moins un programme.";
            if (code === "UE_ANOM_02")
                return "Ajouter au moins un prérequis AAV ou retirer les prérequis UE.";
            return "Action: corriger la donnée source puis sauvegarder.";
        },
        toggleSection(section) {
            this.expandedSections[section] = !this.expandedSections[section];
        },
        isExpanded(section) {
            return this.expandedSections[section] !== false;
        },
        async exportUE(ueId) {
            try {
                const response = await axios.get(`/export/ue/${ueId}`, {
                    responseType: "blob", // 🔥 OBLIGATOIRE pour télécharger un fichier
                });

                // Création d'un lien de téléchargement
                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = url;
                link.download = `UE_${this.ue.code}.xlsx`; // nom du fichier téléchargé
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Erreur de téléchargement :", error);
            }
        },
        async deleteItem() {
            const response = await axios.delete("/ue/delete", {
                params: {
                    id: this.ue.id,
                },
            });
            this.openModalDelete = false;
            this.$router.push({
                name: "tree",
                query: { message: response.data.message },
            });
        },

        async loadUE() {
            try {
                const response = await axios.get("/UEGet/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue = response.data;
                console.log(this.ue);
                /*                 this.ue.semestre = this.ue.semestre === 1 ? "1er" : "2ème";
                const responseError = await axios.get("/Error/UE", {
                    params: {
                        id: this.id,
                    },
                });
                this.errors = responseError.data; */
            } catch (error) {
                console.log(error);
            }
        },
    },

    watch: {
        id: {
            immediate: true, // charge aussi au 1er affichage
            handler() {
                this.loadUE();
            },
        },
    },
};
</script>

<style scoped>
.anomaly-bullet-list {
    padding-left: 1.1rem;
}

.anomaly-bullet-list li {
    margin-bottom: 0.25rem;
}
</style>

