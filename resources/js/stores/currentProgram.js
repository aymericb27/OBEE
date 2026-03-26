import { reactive } from "vue";

const SELECTED_PROGRAM_ID_KEY = "tree.selectedProgramId";
const CURRENT_PROGRAM_KEY = "app.currentProgram";

function parsePositiveInt(value) {
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function readStoredCurrentProgram() {
    try {
        const raw = sessionStorage.getItem(CURRENT_PROGRAM_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        const id = parsePositiveInt(parsed?.id);
        if (!id) return null;
        return {
            id,
            name: typeof parsed?.name === "string" ? parsed.name : "",
            code: typeof parsed?.code === "string" ? parsed.code : "",
        };
    } catch {
        return null;
    }
}

const storedProgram = readStoredCurrentProgram();
const fallbackStoredId = parsePositiveInt(
    sessionStorage.getItem(SELECTED_PROGRAM_ID_KEY),
);

const currentProgramState = reactive({
    id: storedProgram?.id ?? fallbackStoredId ?? null,
    name: storedProgram?.name ?? "",
    code: storedProgram?.code ?? "",
});

function persist() {
    if (!currentProgramState.id) {
        sessionStorage.removeItem(CURRENT_PROGRAM_KEY);
        sessionStorage.removeItem(SELECTED_PROGRAM_ID_KEY);
        return;
    }

    sessionStorage.setItem(SELECTED_PROGRAM_ID_KEY, String(currentProgramState.id));
    sessionStorage.setItem(
        CURRENT_PROGRAM_KEY,
        JSON.stringify({
            id: currentProgramState.id,
            name: currentProgramState.name || "",
            code: currentProgramState.code || "",
        }),
    );
}

export function setCurrentProgram(program) {
    const id = parsePositiveInt(program?.id);
    if (!id) return;

    currentProgramState.id = id;
    currentProgramState.name = typeof program?.name === "string" ? program.name : "";
    currentProgramState.code = typeof program?.code === "string" ? program.code : "";
    persist();
}

export function clearCurrentProgram() {
    currentProgramState.id = null;
    currentProgramState.name = "";
    currentProgramState.code = "";
    persist();
}

export { currentProgramState };
