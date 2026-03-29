<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container pb-3">
        <div class="p-4 border rounded shadow bg-white mt-3">
            <h3 class="primary_color mb-3">
                <i class="fa-solid fa-chart-area mr-2"></i>
                Analyse du programme
            </h3>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-muted mb-0">
                    Programme:
                    <router-link
                        v-if="program.id && program.code"
                        class="PRO ml-1"
                        :to="{ name: 'pro-detail', params: { id: program.id } }"
                    >
                        {{ program.code }}
                    </router-link>
                    <span v-else class="ml-1">{{ program.code }}</span>
                    <span class="ml-1"> - {{ program.name }} </span>
                </p>
                <a
                    href="#"
                    class="small primary_color"
                    @click.prevent="openProgramSwitchModal = true"
                >
                    changer de programme
                </a>
            </div>

            <div class="listComponent">
                <div
                    class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                    @click="toggleSection('uesBySemesterWithErrors')"
                >
                    <h5 class="d-inline-block primary_color mb-0">
                        Liste des UE par semestres comprenant des erreurs
                    </h5>
                    <i
                        class="fa-solid primary_color"
                        :class="
                            isExpanded('uesBySemesterWithErrors')
                                ? 'fa-chevron-down'
                                : 'fa-chevron-up'
                        "
                    ></i>
                </div>

                <div v-show="isExpanded('uesBySemesterWithErrors')">
                    <div class="form-group mb-3">
                        <div class="d-flex align-items-center">
                            <select
                                v-model="selectedAnomalyCode"
                                class="form-control mr-2"
                                @change="loadSpecificAnomalyAnalysis"
                            >
                                <option :value="null">
                                    -- Toutes les anomalies --
                                </option>
                                <option
                                    v-for="anomaly in availableAnomalyCodes"
                                    :key="anomaly.code"
                                    :value="anomaly.code"
                                >
                                    {{ anomaly.label }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="btn btn-outline-primary d-inline-flex align-items-center text-nowrap "
                                title="Export (bientot disponible)"
                            >
                                <i class="fa-solid fa-download mr-1 "></i><span>télécharger sous excel</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="isErrorsLoading || isSpecificLoading"
                        class="text-center py-2"
                    >
                        Chargement...
                    </div>
                    <div
                        v-else-if="displayedAnomalySemesters.length === 0"
                        class="alert alert-success mb-0"
                    >
                        Aucune UE ne comporte d'anomalie pour ce programme.
                    </div>
                    <div v-else>
                        <div
                            v-for="semester in displayedAnomalySemesters"
                            :key="`anom-${semester.id}`"
                            class="border rounded p-3 mb-3"
                        >
                            <h5 class="primary_color mb-3">
                                <i class="fa-solid fa-book-open mr-2"></i>
                                Semestre {{ semester.number }}
                            </h5>

                            <ul class="mb-0 pl-3">
                                <li
                                    v-for="ue in semester.ues"
                                    :key="`anom-ue-${ue.id}`"
                                    class="mb-2"
                                >
                                    <router-link
                                        :to="{
                                            name: 'ue-detail',
                                            params: { id: ue.id },
                                        }"
                                    >
                                        <span class="UE">{{ ue.code }}</span>
                                    </router-link>
                                    <span class="ml-2">{{ ue.name }}</span>
                                    <i
                                        v-if="ue.anomaly_count"
                                        class="fa-solid fa-triangle-exclamation ml-2"
                                        style="color: #f3aa24"
                                        title="UE avec anomalie(s)"
                                    ></i>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="listComponent mt-4">
                <div
                    class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                    @click="toggleSection('aavAatContributionMatrix')"
                >
                    <h5 class="d-inline-block primary_color mb-0">
                        Tableau du niveau de contribution des AAV vers les AAT
                    </h5>
                    <i
                        class="fa-solid primary_color"
                        :class="
                            isExpanded('aavAatContributionMatrix')
                                ? 'fa-chevron-down'
                                : 'fa-chevron-up'
                        "
                    ></i>
                </div>

                <div v-show="isExpanded('aavAatContributionMatrix')">
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="d-flex flex-wrap align-items-center">
                            <select
                                v-model="ueSelectionDraft"
                                class="w-40 form-control mr-2 mb-2"
                                @change="addSelectedUe"
                            >
                                <option :value="null">
                                    -- Sélectionner une UE --
                                </option>
                                <option
                                    v-for="ue in matrixUeOptions"
                                    :key="`ue-select-${ue.id}`"
                                    :value="ue.id"
                                >
                                    {{ ue.code }} - {{ ue.name }}
                                </option>
                            </select>
                            <select
                                v-model="aatSelectionDraft"
                                class="w-35 form-control mr-2 mb-2"
                                @change="addSelectedAat"
                            >
                                <option :value="null">
                                    -- Choisir un AAT --
                                </option>
                                <option
                                    v-for="aat in contributionMatrixAats"
                                    :key="`aat-select-${aat.id}`"
                                    :value="aat.id"
                                >
                                    {{ aat.code }} - {{ aat.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="btn btn-outline-primary d-inline-flex align-items-center text-nowrap mb-2"
                                title="Export (bientot disponible)"
                            >
                                <i class="fa-solid fa-download mr-1"></i><span>télécharger sous excel</span>
                            </button>
                        </div>
                        <div class="mt-1">
                            <div class="form-check mb-1">
                                <input
                                    id="only-participating-aavs"
                                    v-model="onlyParticipatingAavs"
                                    class="form-check-input"
                                    type="checkbox"
                                />
                                <label
                                    class="form-check-label"
                                    for="only-participating-aavs"
                                >
                                    afficher uniquement les AAV qui participe
                                    aux AAT
                                </label>
                            </div>
                            <div class="form-check mb-0">
                                <input
                                    id="only-non-participating-aavs"
                                    v-model="onlyNonParticipatingAavs"
                                    class="form-check-input"
                                    type="checkbox"
                                />
                                <label
                                    class="form-check-label"
                                    for="only-non-participating-aavs"
                                >
                                    afficher uniquement les AAV qui ne
                                    participent pas aux AAT
                                </label>
                            </div>
                        </div>
                        <div
                            v-if="selectedUeIds.length || selectedAatIds.length"
                            class="mt-2"
                        >
                            <div v-if="selectedUeIds.length" class="mb-2">
                                <strong class="mr-2">UE sélectionnées:</strong>
                                <span
                                    v-for="ueId in selectedUeIds"
                                    :key="`selected-ue-${ueId}`"
                                    class="badge selection-chip ue-chip mr-2 mb-1 p-2"
                                >
                                    {{ ueLabelById(ueId) }}
                                    <i
                                        class="fa-solid fa-xmark ml-2 cursor_pointer"
                                        @click="removeSelectedUe(ueId)"
                                    ></i>
                                </span>
                            </div>

                            <div v-if="selectedAatIds.length">
                                <strong class="mr-2">AAT sélectionnés:</strong>
                                <span
                                    v-for="aatId in selectedAatIds"
                                    :key="`selected-aat-${aatId}`"
                                    class="badge selection-chip aat-chip mr-2 mb-1 p-2"
                                >
                                    {{ aatLabelById(aatId) }}
                                    <i
                                        class="fa-solid fa-xmark ml-2 cursor_pointer"
                                        @click="removeSelectedAat(aatId)"
                                    ></i>
                                </span>
                            </div>
                        </div>

                        <div v-else class="small text-muted mt-2">
                            Aucune sélection: toutes les UE et tous les AAT sont
                            affichés.
                        </div>
                    </div>

                    <div v-if="isMatrixLoading" class="text-center py-2">
                        Chargement du tableau...
                    </div>
                    <div
                        v-else-if="
                            filteredContributionMatrixRows.length === 0 ||
                            displayedContributionMatrixAats.length === 0
                        "
                        class="alert alert-info mb-0"
                    >
                        Aucune donnée de contribution disponible pour la
                        sélection actuelle.
                    </div>
                    <div v-else>
                        <div
                            class="d-flex justify-content-end align-items-center gap-2 mb-2 mr-2"
                        >
                            <label class="mb-0 small mr-2">
                                Nombre d'élément à afficher
                            </label>
                            <select
                                v-model.number="matrixPageSize"
                                class="form-control form-select form-select-sm w-auto"
                            >
                                <option
                                    v-for="option in matrixPageSizeOptions"
                                    :key="`matrix-page-size-${option.value}`"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm bg-white">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Semestre</th>
                                        <th>UE</th>
                                        <th>AAV</th>
                                        <th
                                            v-for="aat in displayedContributionMatrixAats"
                                            :key="`aat-head-${aat.id}`"
                                            class="text-center"
                                        >
                                            {{ aat.code }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in paginatedContributionMatrixRows"
                                        :key="`matrix-${row.ue_id}-${row.aav_id}`"
                                    >
                                        <td>S{{ row.semester_number ?? "-" }}</td>
                                        <td>
                                            <router-link
                                                :to="{
                                                    name: 'ue-detail',
                                                    params: { id: row.ue_id },
                                                }"
                                            >
                                                <span class="UE">{{
                                                    row.ue_code
                                                }}</span>
                                            </router-link>
                                        </td>
                                        <td>
                                            <span class="AAV">{{
                                                row.aav_code
                                            }}</span>
                                        </td>
                                        <td
                                            v-for="aat in displayedContributionMatrixAats"
                                            :key="`matrix-cell-${row.ue_id}-${row.aav_id}-${aat.id}`"
                                            class="text-center"
                                        >
                                            <span
                                                v-if="
                                                    contributionValue(
                                                        row,
                                                        aat.id,
                                                    ) !== null
                                                "
                                                :class="
                                                    contributionClass(
                                                        contributionValue(
                                                            row,
                                                            aat.id,
                                                        ),
                                                        aat.level_contribution,
                                                    )
                                                "
                                            >
                                                {{
                                                    contributionValue(
                                                        row,
                                                        aat.id,
                                                    )
                                                }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="d-flex flex-column align-items-center mt-2"
                            v-if="showMatrixPagination"
                        >
                            <small class="text-muted mb-1 text-center">
                                Affichage {{ matrixRangeStart }}-{{ matrixRangeEnd }}
                                / {{ matrixTotalRows }}
                            </small>
                            <ul class="pagination mb-0">
                                <li
                                    class="page-item"
                                    :class="{ disabled: matrixCurrentPage === 1 }"
                                >
                                    <button
                                        class="page-link"
                                        @click="changeMatrixPage(matrixCurrentPage - 1)"
                                        :disabled="matrixCurrentPage === 1"
                                    >
                                        Précédent
                                    </button>
                                </li>
                                <li
                                    v-for="item in matrixPaginationItems"
                                    :key="item.key"
                                    class="page-item"
                                    :class="{
                                        active:
                                            item.type === 'page' &&
                                            matrixCurrentPage === item.value,
                                        disabled: item.type === 'ellipsis',
                                    }"
                                >
                                    <button
                                        v-if="item.type === 'page'"
                                        class="page-link"
                                        @click="changeMatrixPage(item.value)"
                                    >
                                        {{ item.value }}
                                    </button>
                                    <span v-else class="page-link">…</span>
                                </li>
                                <li
                                    class="page-item"
                                    :class="{
                                        disabled: matrixCurrentPage === matrixTotalPages,
                                    }"
                                >
                                    <button
                                        class="page-link"
                                        @click="changeMatrixPage(matrixCurrentPage + 1)"
                                        :disabled="matrixCurrentPage === matrixTotalPages"
                                    >
                                        Suivant
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="listComponent mt-4">
                <div
                    class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                    @click="toggleSection('aatLowContribList')"
                >
                    <h5 class="d-inline-block primary_color mb-0">
                        Liste des AAT dont le nombre de contributions d'AAV est inférieur à un seuil donné
                    </h5>
                    <i
                        class="fa-solid primary_color"
                        :class="
                            isExpanded('aatLowContribList')
                                ? 'fa-chevron-down'
                                : 'fa-chevron-up'
                        "
                    ></i>
                </div>

                <div v-show="isExpanded('aatLowContribList')">
                    <div class="form-group mb-3">
                        <label
                            class="mb-1"
                            for="min-aav-contrib-threshold"
                        >
                            maximum attendu de contributions AAV :
                        </label>
                        <input
                            id="min-aav-contrib-threshold"
                            v-model.number="minAavContributionsThreshold"
                            class="form-control form-control-sm ml-1 d-inline-block"
                            type="number"
                            min="0"
                            style="max-width: 110px"
                        />
                    </div>

                    <div
                        v-if="isMaxContributionLoading"
                        class="text-center py-2"
                    >
                        Chargement...
                    </div>
                    <div
                        v-else-if="aatsBelowThreshold.length === 0"
                        class="alert alert-success mb-0"
                    >
                        Aucun AAT n'est sous le minimum attendu ({{
                            normalizedMinAavContributionsThreshold
                        }}).
                    </div>
                    <div v-else>
                        <div
                            v-for="aat in aatsBelowThreshold"
                            :key="`aat-below-threshold-${aat.id}`"
                            class="border rounded p-3 mb-3"
                        >
                            <h5 class="primary_color mb-2">
                                <i class="fa-solid fa-graduation-cap mr-2"></i>
                                <router-link
                                    class="AAT"
                                    :to="{
                                        name: 'aat-detail',
                                        params: { id: aat.id },
                                    }"
                                >
                                    {{ aat.code }}
                                </router-link>
                                <span class="ml-2 text-dark">{{
                                    aat.name
                                }}</span>
                            </h5>
                            <p class="mb-0 text-muted">
                                Contributions trouvées:
                                {{ aatContributionCounts[aat.id] ?? 0 }}
                            </p>
                            <div class="mt-2">
                                <div class="text-muted mb-1">
                                    AAV concerné(s):
                                </div>
                                <ul
                                    v-if="
                                        (aatContributingAavsByAat[aat.id] || [])
                                            .length
                                    "
                                    class="mb-0 pl-3"
                                >
                                    <li
                                        v-for="aav in aatContributingAavsByAat[
                                            aat.id
                                        ] || []"
                                        :key="`aat-low-aav-${aat.id}-${aav.id}`"
                                        class="mb-1"
                                    >
                                        <router-link
                                            :to="{
                                                name: 'aav-detail',
                                                params: { id: aav.id },
                                            }"
                                        >
                                            <span class="AAV">{{
                                                aav.code
                                            }}</span>
                                        </router-link>
                                        <span class="ml-1">{{ aav.name }}</span>
                                    </li>
                                </ul>
                                <span v-else class="text-muted">Aucun</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="listComponent mt-4">
                <div
                    class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                    @click="toggleSection('aatMaxBelowThresholdList')"
                >
                    <h5 class="d-inline-block primary_color mb-0">
                        Liste des AAT dont la contribution maximale AAV est
                        inférieure à un seuil donné
                    </h5>
                    <i
                        class="fa-solid primary_color"
                        :class="
                            isExpanded('aatMaxBelowThresholdList')
                                ? 'fa-chevron-down'
                                : 'fa-chevron-up'
                        "
                    ></i>
                </div>

                <div v-show="isExpanded('aatMaxBelowThresholdList')">
                    <div class="form-group mb-3">
                        <label
                            class="mb-1"
                            for="max-aav-contrib-threshold"
                        >
                            seuil max de contribution AAV :
                        </label>
                        <input
                            id="max-aav-contrib-threshold"
                            v-model.number="maxAavContributionsThreshold"
                            class="form-control form-control-sm d-inline-block ml-1"
                            type="number"
                            min="0"
                            style="max-width: 110px"
                        />
                    </div>

                    <div v-if="isMatrixLoading" class="text-center py-2">
                        Chargement...
                    </div>
                    <div
                        v-else-if="aatsWithMaxBelowThreshold.length === 0"
                        class="alert alert-success mb-0"
                    >
                        Aucun AAT n'a une contribution maximale inférieure à
                        {{ normalizedMaxAavContributionsThreshold }}.
                    </div>
                    <div v-else>
                        <div
                            v-for="aat in aatsWithMaxBelowThreshold"
                            :key="`aat-max-below-threshold-${aat.id}`"
                            class="border rounded p-3 mb-3"
                        >
                            <h5 class="primary_color mb-2">
                                <i class="fa-solid fa-graduation-cap mr-2"></i>
                                <router-link
                                    class="AAT"
                                    :to="{
                                        name: 'aat-detail',
                                        params: { id: aat.id },
                                    }"
                                >
                                    {{ aat.code }}
                                </router-link>
                                <span class="ml-2 text-dark">{{
                                    aat.name
                                }}</span>
                            </h5>
                            <p class="mb-0 text-muted">
                                Contribution maximale trouvée:
                                {{ aat.max_contribution ?? 0 }}
                            </p>
                            <div class="mt-2">
                                <div class="text-muted mb-1">
                                    AAV concerné(s):
                                </div>
                                <ul
                                    v-if="
                                        (
                                            aatMaxContributingAavsByAat[
                                                aat.id
                                            ] || []
                                        ).length
                                    "
                                    class="mb-0 pl-3"
                                >
                                    <li
                                        v-for="aav in aatMaxContributingAavsByAat[
                                            aat.id
                                        ] || []"
                                        :key="`aat-max-aav-${aat.id}-${aav.id}`"
                                        class="mb-1"
                                    >
                                        <router-link
                                            :to="{
                                                name: 'aav-detail',
                                                params: { id: aav.id },
                                            }"
                                        >
                                            <span class="AAV">{{
                                                aav.code
                                            }}</span>
                                        </router-link>
                                        <span class="ml-1">{{ aav.name }}</span>
                                    </li>
                                </ul>
                                <span v-else class="text-muted">Aucun</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="listComponent mt-4">
                <div
                    class="mb-2 d-flex justify-content-between align-items-center cursor_pointer"
                    @click="toggleSection('ueContributionIncoherences')"
                >
                    <h5 class="d-inline-block primary_color mb-0">
                        Incoherences UE vers AAT et AAV vers AAT
                    </h5>
                    <i
                        class="fa-solid primary_color"
                        :class="
                            isExpanded('ueContributionIncoherences')
                                ? 'fa-chevron-down'
                                : 'fa-chevron-up'
                        "
                    ></i>
                </div>

                <div v-show="isExpanded('ueContributionIncoherences')">
                    <div class="form-group mb-3">
                        <label class="mb-1 d-block" for="incoherence-ue-select">
                            UE donnee (optionnel) :
                        </label>
                        <select
                            id="incoherence-ue-select"
                            v-model="selectedIncoherenceUeId"
                            class="form-control"
                            style="max-width: 420px"
                        >
                            <option :value="null">
                                -- Toutes les UE (vue globale) --
                            </option>
                            <option
                                v-for="ue in incoherenceUeOptions"
                                :key="`incoherence-ue-option-${ue.id}`"
                                :value="ue.id"
                            >
                                {{ ue.code }} - {{ ue.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="isIncoherenceLoading" class="text-center py-2">
                        Chargement...
                    </div>
                    <div v-else>
                        <p class="text-muted mb-2">
                            Vue globale: {{ incoherenceAllUes.length }} UE avec
                            incoherence(s).
                        </p>

                        <div
                            v-if="incoherenceAllUes.length === 0"
                            class="alert alert-success mb-3"
                        >
                            Aucune incoherence detectee sur les contributions
                            UE/AAV de ce programme.
                        </div>
                        <ul v-else class="mb-3 pl-3">
                            <li
                                v-for="item in incoherenceAllUes"
                                :key="`incoherence-global-${item.ue_id}`"
                                class="mb-1"
                            >
                                <router-link
                                    :to="{
                                        name: 'ue-detail',
                                        params: { id: item.ue_id },
                                    }"
                                >
                                    <span class="UE">{{ item.ue_code }}</span>
                                </router-link>
                                <span class="ml-2">{{ item.ue_name }}</span>
                                <i
                                    class="fa-solid fa-triangle-exclamation ml-2"
                                    style="color: #f3aa24"
                                ></i>
                                <span class="ml-2 text-muted"
                                    >({{
                                        item.total_cases
                                    }}
                                    incoherence(s))</span
                                >
                            </li>
                        </ul>

                        <div class="border rounded p-3 bg-light">
                            <h6 class="primary_color mb-2">Detail UE donnee</h6>
                            <div
                                v-if="!selectedIncoherenceUeId"
                                class="text-muted"
                            >
                                Selectionnez une UE pour afficher son detail.
                            </div>
                            <div
                                v-else-if="!selectedIncoherenceEntry"
                                class="alert alert-success mb-0"
                            >
                                Aucune incoherence pour cette UE.
                            </div>
                            <div v-else>
                                <p class="mb-2">
                                    <router-link
                                        :to="{
                                            name: 'ue-detail',
                                            params: {
                                                id: selectedIncoherenceEntry.ue_id,
                                            },
                                        }"
                                    >
                                        <span class="UE">{{
                                            selectedIncoherenceEntry.ue_code
                                        }}</span>
                                    </router-link>
                                    <span class="ml-2">{{
                                        selectedIncoherenceEntry.ue_name
                                    }}</span>
                                    <span class="ml-2 text-muted">
                                        ({{
                                            selectedIncoherenceEntry.total_cases
                                        }}
                                        incoherence(s))
                                    </span>
                                </p>

                                <div class="mb-2">
                                    <strong
                                        >UE declaree vers AAT, mais aucun AAV
                                        vers ces AAT:</strong
                                    >
                                    <ul
                                        v-if="
                                            selectedIncoherenceEntry
                                                .declared_ue_but_no_aav_cases
                                                .length
                                        "
                                        class="mb-0 pl-3"
                                    >
                                        <li
                                            v-for="(
                                                item, index
                                            ) in selectedIncoherenceEntry.declared_ue_but_no_aav_cases"
                                            :key="`incoh-a-${selectedIncoherenceEntry.ue_id}-${index}`"
                                        >
                                            <router-link
                                                :to="{
                                                    name: 'aat-detail',
                                                    params: { id: item.aat_id },
                                                }"
                                            >
                                                <span class="AAT">{{
                                                    item.aat_code
                                                }}</span>
                                            </router-link>
                                            <span class="ml-1">{{
                                                item.aat_name
                                            }}</span>
                                        </li>
                                    </ul>
                                    <div v-else class="text-muted">
                                        Aucun cas.
                                    </div>
                                </div>

                                <div>
                                    <strong
                                        >AAV vers AAT, mais AAT non declare au
                                        niveau UE:</strong
                                    >
                                    <ul
                                        v-if="
                                            selectedIncoherenceEntry
                                                .aav_to_aat_not_declared_in_ue_cases
                                                .length
                                        "
                                        class="mb-0 pl-3"
                                    >
                                        <li
                                            v-for="(
                                                item, index
                                            ) in selectedIncoherenceEntry.aav_to_aat_not_declared_in_ue_cases"
                                            :key="`incoh-b-${selectedIncoherenceEntry.ue_id}-${index}`"
                                        >
                                            <router-link
                                                :to="{
                                                    name: 'aav-detail',
                                                    params: { id: item.aav_id },
                                                }"
                                            >
                                                <span class="AAV">{{
                                                    item.aav_code
                                                }}</span>
                                            </router-link>
                                            <span class="ml-1">-></span>
                                            <router-link
                                                class="ml-1"
                                                :to="{
                                                    name: 'aat-detail',
                                                    params: { id: item.aat_id },
                                                }"
                                            >
                                                <span class="AAT">{{
                                                    item.aat_code
                                                }}</span>
                                            </router-link>
                                            <span class="ml-1">{{
                                                item.aat_name
                                            }}</span>
                                        </li>
                                    </ul>
                                    <div v-else class="text-muted">
                                        Aucun cas.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <ModalList
        :visible="openProgramSwitchModal"
        title="Choisir un programme"
        routeGET="/pro/get"
        type="PRO"
        :singleSelect="true"
        :listToExclude="[{ id: Number(id) }]"
        @close="openProgramSwitchModal = false"
        @selected="handleProgramSwitch"
    />
</template>

<script>
import axios from "axios";
import ModalList from "../modalList.vue";
import { setCurrentProgram } from "../../stores/currentProgram";

export default {
    components: {
        ModalList,
    },
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    data() {
        return {
            program: {
                id: null,
                code: "",
                name: "",
            },
            semestersWithAnomalies: [],
            semestersWithSpecificAnomaly: [],
            availableAnomalyCodes: [],
            selectedAnomalyCode: null,
            expandedSections: {
                uesBySemesterWithErrors: true,
                aavAatContributionMatrix: true,
                aatLowContribList: true,
                ueContributionIncoherences: true,
                aatMaxBelowThresholdList: true,
            },
            isErrorsLoading: false,
            isSpecificLoading: false,
            isMatrixLoading: false,
            isMaxContributionLoading: false,
            isIncoherenceLoading: false,
            contributionMatrixAats: [],
            contributionMatrixRows: [],
            ueSelectionDraft: null,
            aatSelectionDraft: null,
            selectedUeIds: [],
            selectedAatIds: [],
            selectedIncoherenceUeId: null,
            onlyParticipatingAavs: false,
            onlyNonParticipatingAavs: false,
            minAavContributionsThreshold: 1,
            maxAavContributionsThreshold: 1,
            matrixCurrentPage: 1,
            matrixPageSize: 10,
            matrixPageSizeOptions: [
                { value: 10, label: "10" },
                { value: 25, label: "25" },
                { value: 50, label: "50" },
                { value: 100, label: "100" },
                { value: -1, label: "Tout" },
            ],
            aatsWithMaxBelowThreshold: [],
            incoherenceUeOptions: [],
            incoherenceAllUes: [],
            openProgramSwitchModal: false,
        };
    },
    computed: {
        displayedAnomalySemesters() {
            if (!this.selectedAnomalyCode) return this.semestersWithAnomalies;
            return this.semestersWithSpecificAnomaly;
        },
        matrixUeOptions() {
            const map = new Map();
            for (const row of this.contributionMatrixRows || []) {
                const id = Number(row?.ue_id);
                if (!id || map.has(id)) continue;
                map.set(id, {
                    id,
                    code: row?.ue_code || "",
                    name: row?.ue_name || "",
                });
            }
            return Array.from(map.values()).sort((a, b) =>
                (a.code || "").localeCompare(b.code || "", undefined, {
                    numeric: true,
                    sensitivity: "base",
                }),
            );
        },
        filteredContributionMatrixRows() {
            const rows = Array.isArray(this.contributionMatrixRows)
                ? [...this.contributionMatrixRows]
                : [];

            let filtered = rows;
            if (this.selectedUeIds.length) {
                const selected = new Set(
                    this.selectedUeIds.map((id) => Number(id)),
                );
                filtered = filtered.filter((row) =>
                    selected.has(Number(row?.ue_id)),
                );
            }

            if (!this.onlyParticipatingAavs) {
                if (!this.onlyNonParticipatingAavs) {
                    return filtered;
                }
            }

            const aatIds = this.displayedContributionMatrixAats.map((aat) =>
                Number(aat.id),
            );

            if (this.onlyParticipatingAavs) {
                return filtered.filter((row) =>
                    aatIds.some(
                        (aatId) => this.contributionValue(row, aatId) !== null,
                    ),
                );
            }

            return filtered.filter((row) =>
                aatIds.every(
                    (aatId) => this.contributionValue(row, aatId) === null,
                ),
            );
        },
        matrixTotalRows() {
            return this.filteredContributionMatrixRows.length;
        },
        effectiveMatrixPageSize() {
            if (Number(this.matrixPageSize) <= 0) {
                return Math.max(1, this.matrixTotalRows);
            }
            return Number(this.matrixPageSize) || 10;
        },
        matrixTotalPages() {
            return Math.max(
                1,
                Math.ceil(this.matrixTotalRows / this.effectiveMatrixPageSize),
            );
        },
        matrixPaginationItems() {
            const total = this.matrixTotalPages;
            const current = this.matrixCurrentPage;

            const pageItem = (value) => ({
                type: "page",
                value,
                key: `page-${value}`,
            });
            const ellipsisItem = (key) => ({
                type: "ellipsis",
                value: null,
                key: `ellipsis-${key}`,
            });

            if (total <= 7) {
                return Array.from({ length: total }, (_, index) =>
                    pageItem(index + 1),
                );
            }

            const items = [pageItem(1)];

            if (current <= 4) {
                for (let page = 2; page <= 5; page++) {
                    items.push(pageItem(page));
                }
                items.push(ellipsisItem("right"));
                items.push(pageItem(total));
                return items;
            }

            if (current >= total - 3) {
                items.push(ellipsisItem("left"));
                for (let page = total - 4; page <= total; page++) {
                    items.push(pageItem(page));
                }
                return items;
            }

            items.push(ellipsisItem("left"));
            items.push(pageItem(current - 1));
            items.push(pageItem(current));
            items.push(pageItem(current + 1));
            items.push(ellipsisItem("right"));
            items.push(pageItem(total));

            return items;
        },
        paginatedContributionMatrixRows() {
            const size = this.effectiveMatrixPageSize;
            const start = (this.matrixCurrentPage - 1) * size;
            return this.filteredContributionMatrixRows.slice(start, start + size);
        },
        showMatrixPagination() {
            return this.matrixTotalRows > this.effectiveMatrixPageSize;
        },
        matrixRangeStart() {
            if (this.matrixTotalRows === 0) return 0;
            return (this.matrixCurrentPage - 1) * this.effectiveMatrixPageSize + 1;
        },
        matrixRangeEnd() {
            return Math.min(
                this.matrixCurrentPage * this.effectiveMatrixPageSize,
                this.matrixTotalRows,
            );
        },
        displayedContributionMatrixAats() {
            const aats = Array.isArray(this.contributionMatrixAats)
                ? this.contributionMatrixAats
                : [];
            if (!this.selectedAatIds.length) return aats;
            const selected = new Set(
                this.selectedAatIds.map((id) => Number(id)),
            );
            return aats.filter((aat) => selected.has(Number(aat?.id)));
        },
        normalizedMinAavContributionsThreshold() {
            const n = Number(this.minAavContributionsThreshold);
            if (!Number.isFinite(n)) return 0;
            return Math.max(0, Math.floor(n));
        },
        aatContributionCounts() {
            const counts = {};
            const aats = Array.isArray(this.contributionMatrixAats)
                ? this.contributionMatrixAats
                : [];
            aats.forEach((aat) => {
                counts[Number(aat.id)] = 0;
            });

            const rows = Array.isArray(this.contributionMatrixRows)
                ? this.contributionMatrixRows
                : [];
            for (const row of rows) {
                for (const aat of aats) {
                    const aatId = Number(aat.id);
                    if (this.contributionValue(row, aatId) !== null) {
                        counts[aatId] = (counts[aatId] || 0) + 1;
                    }
                }
            }
            return counts;
        },
        aatsBelowThreshold() {
            const threshold = this.normalizedMinAavContributionsThreshold;
            const aats = Array.isArray(this.contributionMatrixAats)
                ? this.contributionMatrixAats
                : [];
            return aats.filter(
                (aat) => {
                    const count = this.aatContributionCounts[Number(aat.id)] || 0;
                    return count > 0 && count < threshold;
                },
            );
        },
        aatContributingAavsByAat() {
            const byAat = {};
            const seenByAat = {};
            const aats = Array.isArray(this.contributionMatrixAats)
                ? this.contributionMatrixAats
                : [];
            const rows = Array.isArray(this.contributionMatrixRows)
                ? this.contributionMatrixRows
                : [];

            for (const aat of aats) {
                const aatId = Number(aat?.id);
                if (!aatId) continue;
                byAat[aatId] = [];
                seenByAat[aatId] = new Set();
            }

            for (const row of rows) {
                const aavId = Number(row?.aav_id);
                if (!aavId) continue;

                for (const aat of aats) {
                    const aatId = Number(aat?.id);
                    if (!aatId) continue;
                    if (this.contributionValue(row, aatId) === null) continue;
                    if (seenByAat[aatId].has(aavId)) continue;

                    seenByAat[aatId].add(aavId);
                    byAat[aatId].push({
                        id: aavId,
                        code: row?.aav_code || `AAV#${aavId}`,
                        name: row?.aav_name || "",
                    });
                }
            }

            for (const aatId of Object.keys(byAat)) {
                byAat[aatId] = byAat[aatId].sort((a, b) =>
                    (a.code || "").localeCompare(b.code || "", undefined, {
                        numeric: true,
                        sensitivity: "base",
                    }),
                );
            }

            return byAat;
        },
        aatMaxContributingAavsByAat() {
            const byAat = {};
            const maxByAat = {};
            const seenByAat = {};
            const aats = Array.isArray(this.contributionMatrixAats)
                ? this.contributionMatrixAats
                : [];
            const rows = Array.isArray(this.contributionMatrixRows)
                ? this.contributionMatrixRows
                : [];

            for (const aat of aats) {
                const aatId = Number(aat?.id);
                if (!aatId) continue;
                byAat[aatId] = [];
                maxByAat[aatId] = null;
                seenByAat[aatId] = new Set();
            }

            for (const row of rows) {
                const aavId = Number(row?.aav_id);
                if (!aavId) continue;

                for (const aat of aats) {
                    const aatId = Number(aat?.id);
                    if (!aatId) continue;
                    const rawValue = this.contributionValue(row, aatId);
                    if (rawValue === null) continue;
                    const value = Number(rawValue);
                    if (!Number.isFinite(value)) continue;

                    if (maxByAat[aatId] === null || value > maxByAat[aatId]) {
                        maxByAat[aatId] = value;
                        byAat[aatId] = [
                            {
                                id: aavId,
                                code: row?.aav_code || `AAV#${aavId}`,
                                name: row?.aav_name || "",
                            },
                        ];
                        seenByAat[aatId] = new Set([aavId]);
                        continue;
                    }

                    if (
                        value === maxByAat[aatId] &&
                        !seenByAat[aatId].has(aavId)
                    ) {
                        seenByAat[aatId].add(aavId);
                        byAat[aatId].push({
                            id: aavId,
                            code: row?.aav_code || `AAV#${aavId}`,
                            name: row?.aav_name || "",
                        });
                    }
                }
            }

            for (const aatId of Object.keys(byAat)) {
                byAat[aatId] = byAat[aatId].sort((a, b) =>
                    (a.code || "").localeCompare(b.code || "", undefined, {
                        numeric: true,
                        sensitivity: "base",
                    }),
                );
            }

            return byAat;
        },
        normalizedMaxAavContributionsThreshold() {
            const n = Number(this.maxAavContributionsThreshold);
            if (!Number.isFinite(n)) return 0;
            return Math.max(0, Math.floor(n));
        },
        selectedIncoherenceEntry() {
            if (!this.selectedIncoherenceUeId) return null;
            const targetId = Number(this.selectedIncoherenceUeId);
            return (
                (this.incoherenceAllUes || []).find(
                    (item) => Number(item?.ue_id) === targetId,
                ) || null
            );
        },
    },
    methods: {
        syncCurrentProgram(programLike = null) {
            const source = programLike || this.program || {};
            const id = Number(source?.id ?? this.id);
            if (!Number.isInteger(id) || id <= 0) return;

            setCurrentProgram({
                id,
                code:
                    typeof source?.code === "string"
                        ? source.code
                        : this.program?.code || "",
                name:
                    typeof source?.name === "string"
                        ? source.name
                        : this.program?.name || "",
            });
        },
        toggleSection(section) {
            this.expandedSections[section] = !this.expandedSections[section];
        },
        isExpanded(section) {
            return this.expandedSections[section] !== false;
        },
        contributionValue(row, aatId) {
            const value = row?.contributions?.[aatId];
            return value === undefined ? null : value;
        },
        contributionClass(value, max) {
            const safeMax = Number(max) > 0 ? Number(max) : 10;
            const oneThird = Math.ceil(safeMax / 3);
            const twoThirds = Math.ceil((safeMax * 2) / 3);
            if (value == 10) return "strong_mapping strong_ten_mapping";
            if (value > twoThirds) return "strong_mapping";
            if (value > oneThird) return "medium_mapping";
            return "weak_mapping";
        },
        changeMatrixPage(page) {
            const target = Number(page);
            if (!Number.isInteger(target)) return;
            if (target < 1 || target > this.matrixTotalPages) return;
            this.matrixCurrentPage = target;
        },
        resetMatrixPagination() {
            this.matrixCurrentPage = 1;
        },
        clampMatrixPagination() {
            if (this.matrixCurrentPage > this.matrixTotalPages) {
                this.matrixCurrentPage = this.matrixTotalPages;
            }
            if (this.matrixCurrentPage < 1) {
                this.matrixCurrentPage = 1;
            }
        },
        selectionStorageKey() {
            return `programAnalysis.selection.${this.id}`;
        },
        persistSelections() {
            try {
                const payload = {
                    ue_ids: this.selectedUeIds.map((id) => Number(id)),
                    aat_ids: this.selectedAatIds.map((id) => Number(id)),
                    selected_incoherence_ue_id: this.selectedIncoherenceUeId
                        ? Number(this.selectedIncoherenceUeId)
                        : null,
                    only_participating_aavs: Boolean(
                        this.onlyParticipatingAavs,
                    ),
                    only_non_participating_aavs: Boolean(
                        this.onlyNonParticipatingAavs,
                    ),
                    min_aav_contributions_threshold:
                        this.normalizedMinAavContributionsThreshold,
                    max_aav_contributions_threshold:
                        this.normalizedMaxAavContributionsThreshold,
                    matrix_page_size: Number(this.matrixPageSize) || 10,
                };
                localStorage.setItem(
                    this.selectionStorageKey(),
                    JSON.stringify(payload),
                );
            } catch (e) {}
        },
        restoreSelections() {
            try {
                const raw = localStorage.getItem(this.selectionStorageKey());
                if (!raw) {
                    this.selectedUeIds = [];
                    this.selectedAatIds = [];
                    return;
                }
                const parsed = JSON.parse(raw);
                this.selectedUeIds = Array.isArray(parsed?.ue_ids)
                    ? parsed.ue_ids
                          .map((id) => Number(id))
                          .filter((id) => id > 0)
                    : [];
                this.selectedAatIds = Array.isArray(parsed?.aat_ids)
                    ? parsed.aat_ids
                          .map((id) => Number(id))
                          .filter((id) => id > 0)
                    : [];
                this.selectedIncoherenceUeId =
                    Number.isFinite(
                        Number(parsed?.selected_incoherence_ue_id),
                    ) && Number(parsed?.selected_incoherence_ue_id) > 0
                        ? Number(parsed.selected_incoherence_ue_id)
                        : null;
                this.onlyParticipatingAavs = Boolean(
                    parsed?.only_participating_aavs,
                );
                this.onlyNonParticipatingAavs = Boolean(
                    parsed?.only_non_participating_aavs,
                );
                this.minAavContributionsThreshold = Number.isFinite(
                    Number(parsed?.min_aav_contributions_threshold),
                )
                    ? Math.max(
                          0,
                          Math.floor(
                              Number(parsed.min_aav_contributions_threshold),
                          ),
                      )
                    : 1;
                this.maxAavContributionsThreshold = Number.isFinite(
                    Number(parsed?.max_aav_contributions_threshold),
                )
                    ? Math.max(
                          0,
                          Math.floor(
                              Number(parsed.max_aav_contributions_threshold),
                          ),
                      )
                    : 1;
                const parsedPageSize = Number(parsed?.matrix_page_size);
                const allowedPageSizes = new Set(
                    this.matrixPageSizeOptions.map((option) =>
                        Number(option.value),
                    ),
                );
                this.matrixPageSize = allowedPageSizes.has(parsedPageSize)
                    ? parsedPageSize
                    : 10;
            } catch (e) {
                this.selectedUeIds = [];
                this.selectedAatIds = [];
                this.selectedIncoherenceUeId = null;
                this.onlyParticipatingAavs = false;
                this.onlyNonParticipatingAavs = false;
                this.minAavContributionsThreshold = 1;
                this.maxAavContributionsThreshold = 1;
                this.matrixPageSize = 10;
            }
        },
        pruneSelections() {
            const validUeIds = new Set(
                this.matrixUeOptions.map((ue) => Number(ue.id)),
            );
            const validAatIds = new Set(
                (this.contributionMatrixAats || []).map((aat) =>
                    Number(aat.id),
                ),
            );
            this.selectedUeIds = this.selectedUeIds.filter((id) =>
                validUeIds.has(Number(id)),
            );
            this.selectedAatIds = this.selectedAatIds.filter((id) =>
                validAatIds.has(Number(id)),
            );
            if (
                this.selectedIncoherenceUeId !== null &&
                !validUeIds.has(Number(this.selectedIncoherenceUeId))
            ) {
                this.selectedIncoherenceUeId = null;
            }
            this.clampMatrixPagination();
            this.persistSelections();
        },
        addSelectedUe() {
            if (this.ueSelectionDraft === null) return;
            const id = Number(this.ueSelectionDraft);
            if (!this.selectedUeIds.includes(id)) {
                this.selectedUeIds.push(id);
                this.persistSelections();
            }
            this.ueSelectionDraft = null;
            this.resetMatrixPagination();
        },
        removeSelectedUe(id) {
            this.selectedUeIds = this.selectedUeIds.filter(
                (item) => Number(item) !== Number(id),
            );
            this.persistSelections();
            this.resetMatrixPagination();
        },
        addSelectedAat() {
            if (this.aatSelectionDraft === null) return;
            const id = Number(this.aatSelectionDraft);
            if (!this.selectedAatIds.includes(id)) {
                this.selectedAatIds.push(id);
                this.persistSelections();
            }
            this.aatSelectionDraft = null;
            this.resetMatrixPagination();
        },
        removeSelectedAat(id) {
            this.selectedAatIds = this.selectedAatIds.filter(
                (item) => Number(item) !== Number(id),
            );
            this.persistSelections();
            this.resetMatrixPagination();
        },
        handleProgramSwitch(selectedItems) {
            this.openProgramSwitchModal = false;
            const selected = Array.isArray(selectedItems)
                ? selectedItems[0]
                : null;
            const selectedId = Number(selected?.id || 0);
            if (!selectedId || selectedId === Number(this.id)) {
                return;
            }

            this.syncCurrentProgram(selected);
            this.$router.push({
                name: "programAnalysis",
                params: { id: selectedId },
            });
        },
        ueLabelById(id) {
            const ue = this.matrixUeOptions.find(
                (item) => Number(item.id) === Number(id),
            );
            if (!ue) return `UE #${id}`;
            if (ue.code && ue.name) return `${ue.code} - ${ue.name}`;
            return ue.code || ue.name || `UE #${id}`;
        },
        aatLabelById(id) {
            const aat = (this.contributionMatrixAats || []).find(
                (item) => Number(item.id) === Number(id),
            );
            if (!aat) return `AAT #${id}`;
            if (aat.code && aat.name) return `${aat.code} - ${aat.name}`;
            return aat.code || aat.name || `AAT #${id}`;
        },
        async loadErrorsBlock() {
            this.isErrorsLoading = true;
            try {
                const response = await axios.get(
                    "/pro/analysis/ues-with-errors",
                    {
                        params: { id: this.id },
                    },
                );
                const payload = response.data || {};
                this.program = {
                    id: payload?.program?.id ?? this.program.id ?? null,
                    code: payload?.program?.code || "",
                    name: payload?.program?.name || "",
                };
                this.syncCurrentProgram(this.program);
                this.semestersWithAnomalies = Array.isArray(
                    payload.semesters_with_anomalies,
                )
                    ? payload.semesters_with_anomalies
                    : [];
            } catch (error) {
                console.error("Erreur chargement analyse programme :", error);
                this.semestersWithAnomalies = [];
            } finally {
                this.isErrorsLoading = false;
            }
        },
        async loadSpecificAnomalyAnalysis() {
            this.isSpecificLoading = true;
            try {
                const response = await axios.get(
                    "/pro/analysis/ues-with-specific-error",
                    {
                        params: {
                            id: this.id,
                            anomaly_code: this.selectedAnomalyCode || undefined,
                        },
                    },
                );
                const payload = response.data || {};
                this.program = {
                    id: payload?.program?.id ?? this.program.id ?? null,
                    code: payload?.program?.code || this.program.code || "",
                    name: payload?.program?.name || this.program.name || "",
                };
                this.syncCurrentProgram(this.program);
                this.availableAnomalyCodes = Array.isArray(
                    payload.available_anomaly_codes,
                )
                    ? payload.available_anomaly_codes
                    : [];
                this.semestersWithSpecificAnomaly = Array.isArray(
                    payload.semesters_with_specific_anomaly,
                )
                    ? payload.semesters_with_specific_anomaly
                    : [];
            } catch (error) {
                console.error("Erreur chargement anomalie spécifique :", error);
                this.semestersWithSpecificAnomaly = [];
            } finally {
                this.isSpecificLoading = false;
            }
        },
        async loadContributionMatrixBlock() {
            this.isMatrixLoading = true;
            try {
                const response = await axios.get(
                    "/pro/analysis/contribution-matrix",
                    {
                        params: { id: this.id },
                    },
                );
                const payload = response.data || {};
                const matrix = payload.aav_aat_contribution_matrix || {};
                this.program = {
                    id: payload?.program?.id ?? this.program.id ?? null,
                    code: payload?.program?.code || this.program.code || "",
                    name: payload?.program?.name || this.program.name || "",
                };
                this.syncCurrentProgram(this.program);
                this.contributionMatrixAats = Array.isArray(matrix.aats)
                    ? matrix.aats
                    : [];
                this.contributionMatrixRows = Array.isArray(matrix.rows)
                    ? matrix.rows
                    : [];
                this.restoreSelections();
                this.pruneSelections();
                this.clampMatrixPagination();
            } catch (error) {
                console.error(
                    "Erreur chargement tableau contribution :",
                    error,
                );
                this.contributionMatrixAats = [];
                this.contributionMatrixRows = [];
            } finally {
                this.isMatrixLoading = false;
            }
        },
        async loadAatMaxContributionBlock() {
            this.isMaxContributionLoading = true;
            try {
                const response = await axios.get(
                    "/pro/analysis/aat-max-contribution-below",
                    {
                        params: {
                            id: this.id,
                            n: this.normalizedMaxAavContributionsThreshold,
                        },
                    },
                );
                const payload = response.data || {};
                this.program = {
                    id: payload?.program?.id ?? this.program.id ?? null,
                    code: payload?.program?.code || this.program.code || "",
                    name: payload?.program?.name || this.program.name || "",
                };
                this.syncCurrentProgram(this.program);
                this.aatsWithMaxBelowThreshold = Array.isArray(
                    payload.aats_with_max_contribution_below,
                )
                    ? payload.aats_with_max_contribution_below
                    : [];
            } catch (error) {
                console.error(
                    "Erreur chargement AAT max contribution :",
                    error,
                );
                this.aatsWithMaxBelowThreshold = [];
            } finally {
                this.isMaxContributionLoading = false;
            }
        },
        async loadContributionIncoherencesBlock() {
            this.isIncoherenceLoading = true;
            try {
                const response = await axios.get(
                    "/pro/analysis/contribution-incoherences",
                    {
                        params: {
                            id: this.id,
                        },
                    },
                );
                const payload = response.data || {};
                this.program = {
                    id: payload?.program?.id ?? this.program.id ?? null,
                    code: payload?.program?.code || this.program.code || "",
                    name: payload?.program?.name || this.program.name || "",
                };
                this.syncCurrentProgram(this.program);
                this.incoherenceUeOptions = Array.isArray(payload.ue_options)
                    ? payload.ue_options
                    : [];
                this.incoherenceAllUes = Array.isArray(
                    payload.all_ues_incoherences,
                )
                    ? payload.all_ues_incoherences
                    : [];

                const validUeIds = new Set(
                    this.incoherenceUeOptions.map((ue) => Number(ue.id)),
                );
                if (
                    this.selectedIncoherenceUeId !== null &&
                    !validUeIds.has(Number(this.selectedIncoherenceUeId))
                ) {
                    this.selectedIncoherenceUeId = null;
                }
            } catch (error) {
                console.error(
                    "Erreur chargement incoherences UE/AAV/AAT :",
                    error,
                );
                this.incoherenceUeOptions = [];
                this.incoherenceAllUes = [];
            } finally {
                this.isIncoherenceLoading = false;
            }
        },
        async loadAnalysisBlocks() {
            await Promise.all([
                this.loadErrorsBlock(),
                this.loadSpecificAnomalyAnalysis(),
                this.loadContributionMatrixBlock(),
                this.loadAatMaxContributionBlock(),
                this.loadContributionIncoherencesBlock(),
            ]);
        },
    },
    mounted() {
        this.loadAnalysisBlocks();
    },
    watch: {
        id() {
            this.selectedAnomalyCode = null;
            this.selectedIncoherenceUeId = null;
            this.resetMatrixPagination();
            this.loadAnalysisBlocks();
        },
        onlyParticipatingAavs() {
            if (this.onlyParticipatingAavs) {
                this.onlyNonParticipatingAavs = false;
            }
            this.resetMatrixPagination();
            this.persistSelections();
        },
        onlyNonParticipatingAavs() {
            if (this.onlyNonParticipatingAavs) {
                this.onlyParticipatingAavs = false;
            }
            this.resetMatrixPagination();
            this.persistSelections();
        },
        matrixPageSize() {
            this.resetMatrixPagination();
            this.persistSelections();
        },
        filteredContributionMatrixRows() {
            this.clampMatrixPagination();
        },
        minAavContributionsThreshold() {
            this.persistSelections();
        },
        maxAavContributionsThreshold() {
            this.persistSelections();
            this.loadAatMaxContributionBlock();
        },
        selectedIncoherenceUeId() {
            this.persistSelections();
        },
    },
};
</script>

<style scoped>
.selection-chip {
    border: 1px solid transparent;
    font-weight: 600;
}

.selection-chip i {
    color: #fff;
}

.ue-chip {
    background-color: #92429d;
    border-color: #92429d;
    color: #fff;
}

.aat-chip {
    background-color: #b80f0f;
    border-color: #b80f0f;
    color: #fff;
}

.table td {
    padding: 0.5rem;
}
</style>
