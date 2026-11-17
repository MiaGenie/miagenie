import { computed } from "vue";

const HEX = /^#[0-9a-f]{3,8}$/i;

/**
 * The props every strategy block takes, so `SchemaNode` can hand a node to whichever block draws it.
 */
export const blockProps = {
    schema: {
        type: Object,
        default: () => ({}),
    },
    modelValue: {
        default: null,
    },
    label: {
        type: String,
        default: "",
    },
    depth: {
        type: Number,
        default: 0,
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    /**
     * What the sub-field tree says about this node, shaped like the schema:
     * `{editable, block?, class?, properties?, items?}`. Absent means nothing is locked and
     * nothing is styled.
     */
    meta: {
        type: Object,
        default: null,
    },
    /**
     * Apply `editable` to structure only — no adding or removing items — and leave values alone.
     * The step review runs this way: it is where the customer corrects what the model wrote.
     */
    structureOnly: {
        type: Boolean,
        default: false,
    },
};

/**
 * Optional properties are emitted as a union with null, so the meaningful type is the one that
 * is not "null".
 */
export const typeOf = (schema) => {
    const type = schema?.type;

    return Array.isArray(type) ? type.find((entry) => entry !== "null") : type;
};

export const labelOf = (key, schema) => schema?.title || key.replace(/_/g, " ");

export const isHex = (value) => typeof value === "string" && HEX.test(value.trim());

const childTypes = (schema) => Object.values(schema?.properties ?? {}).map(typeOf);

/**
 * The layout a node is drawn with when its sub-field names none: the schema's shape decides.
 *
 * An object whose children are all booleans is a multiple choice — the shape a CHECKBOX field
 * compiles to now that its options live in the sub-field tree — and one whose children are all
 * objects reads best as a grid of cards rather than a stack.
 */
export const inferBlock = (schema) => {
    const type = typeOf(schema);
    const children = childTypes(schema);

    if (type === "object") {
        if (children.length > 0 && children.every((child) => child === "boolean")) {
            return "choices";
        }

        if (children.length > 1 && children.every((child) => child === "object")) {
            return "cards";
        }

        return "stack";
    }

    if (type === "array") {
        return typeOf(schema?.items) === "object" ? "numbered_cards" : "bullets";
    }

    if (type === "boolean") {
        return "checkbox";
    }

    return "text";
};

/**
 * Whether a block can draw this schema. The server only sends a block that fits the sub-field
 * tree (StrategyBlock::fits), but the schema the page gets is compiled separately, so it is
 * checked again rather than drawn wrong.
 */
export const fits = (block, schema) => {
    const type = typeOf(schema);
    const children = childTypes(schema);

    switch (block) {
        case "stack":
        case "cards":
            return type === "object";
        case "choices":
            return type === "object" && children.length > 0 && children.every((child) => child === "boolean");
        case "bullets":
            return type === "array" && typeOf(schema?.items) !== "object";
        case "numbered_cards":
            return type === "array" && typeOf(schema?.items) === "object";
        case "checkbox":
            return type === "boolean";
        case "text":
            return !["object", "array", "boolean"].includes(type);
        default:
            return false;
    }
};

export const resolveBlock = (schema, meta) => (fits(meta?.block, schema) ? meta.block : inferBlock(schema));

/**
 * Whether a block's node may be changed, and whether its shape may be.
 *
 * An unlocked page is the first condition of both: `readOnly` is the strategy being approved, or
 * some ancestor page-level lock, and nothing below it is editable regardless of the tree.
 */
const useSchemaNode = (props) => {
    const locked = computed(() => props.readOnly || (!props.structureOnly && props.meta?.editable === false));

    const structureLocked = computed(() => props.readOnly || props.meta?.editable === false);

    const childMeta = (key) => props.meta?.properties?.[key] ?? null;

    const itemMeta = (key) => props.meta?.items?.properties?.[key] ?? null;

    return {
        locked,
        structureLocked,
        childMeta,
        itemMeta,
    };
};

export default useSchemaNode;
