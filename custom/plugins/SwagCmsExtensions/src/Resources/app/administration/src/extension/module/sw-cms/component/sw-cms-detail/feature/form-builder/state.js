const { EntityCollection } = Shopware.Data;
const { setReactive } = Shopware.Application.view;

// Local helper method to duplicate a field
const duplicateField = (state, field, groupId, fieldRepository, context) => {
    const duplicatedField = fieldRepository.create(context);
    const group = state.form.groups.get(groupId);
    const fieldKeysBlocklist = ['id', 'groupId', 'group', 'position'];

    duplicatedField.position = group.fields.length + 1;

    Object.keys(field).forEach((key) => {
        if (fieldKeysBlocklist.includes(key)) {
            return;
        }

        duplicatedField[key] = field[key];
    });

    return duplicatedField;
};

// Local helper method to sanitize quantities
const sanitizePositions = (fieldCollection, movedPosition) => {
    fieldCollection.forEach((field) => {
        if (field.position < movedPosition) {
            return;
        }

        field.position -= 1;
    });
};

export default {
    namespaced: true,

    state() {
        return {
            form: null,
            activeItem: null,
        };
    },

    getters: {
        form: (state) => {
            return state.form;
        },

        template: (state) => (templateName, formRepository, groupRepository, fieldRepository) => {
            const formTemplate = formRepository.create(Shopware.Context.api);
            formTemplate.isTemplate = true;
            formTemplate.technicalName = templateName;
            formTemplate.title = state.form.title;
            formTemplate.successMessage = state.form.successMessage;
            formTemplate.receivers = state.form.receivers;
            formTemplate.mailTemplateId = state.form.mailTemplateId;

            state.form.groups.forEach((group) => {
                const g = groupRepository.create(Shopware.Context.api);
                g.technicalName = group.technicalName;
                g.title = group.title;
                g.position = group.position;

                group.fields.forEach((field) => {
                    const f = fieldRepository.create(Shopware.Context.api);
                    f.position = field.position;
                    f.width = field.width;
                    f.type = field.type;
                    f.technicalName = field.technicalName;
                    f.required = field.required;
                    f.label = field.label;
                    f.placeholder = field.placeholder;
                    f.errorMessage = field.errorMessage;
                    f.config = field.config;

                    g.fields.add(f);
                });

                formTemplate.groups.add(g);
            });

            return formTemplate;
        },
    },

    mutations: {
        setForm(state, form) {
            state.form = form;
        },

        setFormProperty(state, { property, value }) {
            state.form[property] = value;
        },

        setActiveItem(state, activeItem) {
            state.activeItem = activeItem;
        },

        setFieldProperty(state, { fieldId, property, value }) {
            const group = state.form.groups.find(formGroup => formGroup.fields.get(fieldId));
            group.fields.get(fieldId)[property] = value;
        },

        setFieldConfigProperty(state, { fieldId, property, value }) {
            const group = state.form.groups.find(formGroup => formGroup.fields.get(fieldId));
            const field = group.fields.get(fieldId);

            field.config = {
                ...(field.translated?.config ?? {}),
                [property]: value,
            };

            field.translated = { ...field.translated };
            field.translated.config = { ...field.config };
        },

        setFieldType(state, { fieldId, type }) {
            const group = state.form.groups.find(formGroup => formGroup.fields.get(fieldId));
            const field = state.form.groups.get(group.id).fields.get(fieldId);
            field.config = {};

            switch (type) {
                case 'number':
                    setReactive(field.config, 'min', null);
                    setReactive(field.config, 'max', null);
                    setReactive(field.config, 'step', 1);
                    break;
                case 'select':
                    setReactive(field.config, 'entity', null);
                    break;
                case 'textarea':
                    setReactive(field.config, 'scalable', true);
                    setReactive(field.config, 'rows', 5);
                    break;
                case 'checkbox':
                    setReactive(field.config, 'default', false);
                    break;
                case 'text':
                case 'email':
                default:
                    break;
            }

            field.type = type;
        },

        changeFieldSelectConfig(state, { fieldId, mode }) {
            const group = state.form.groups.find(formGroup => formGroup.fields.get(fieldId));
            const field = state.form.groups.get(group.id).fields.get(fieldId);

            if (!field?.translated?.config) {
                return;
            }
            setReactive(field, 'config', {});

            if (mode === 'custom') {
                setReactive(field.config, 'options', []);
                return;
            }
            setReactive(field.config, 'entity', null);
        },

        setGroupProperty(state, { groupId, property, value }) {
            const group = state.form.groups.get(groupId);
            group[property] = value;
        },

        resetState(state) {
            state.form = null;
            state.activeItem = null;
        },

        addField(state, { groupId, repository, context, prefix }) {
            const group = state.form.groups.get(groupId);
            const field = repository.create(context);
            const overallFieldCount = state.form.groups.reduce((acc, g) => {
                acc += g.fields.length;

                return acc;
            }, 0);

            field.id = Shopware.Utils.createId();
            field.technicalName = `${prefix}${overallFieldCount + 1}`;
            field.position = group.fields.length + 1;
            field.type = 'text';
            field.width = 12;
            field.config = {};

            group.fields.add(field);
            state.activeItem = field;
        },

        addGroup(state, { repository, prefix, context }) {
            const group = repository.create(context);

            group.technicalName = `${prefix}${state.form.groups.length + 1}`;
            group.position = state.form.groups.length + 1;
            state.form.groups.add(group);
            state.activeItem = group.fields.first();
        },

        moveItem(state, { itemId, callback }) {
            let group = state.form.groups.find(formGroup => formGroup.id === itemId);

            // itemId is not a group find the group containing a field with the id itemId
            if (!group) {
                group = state.form.groups.find(formGroup => formGroup.fields.get(itemId));
                callback(group.fields, group.fields.get(itemId));
                return;
            }

            callback(state.form.groups, state.form.groups.get(itemId));
        },

        duplicateGroup(state, { groupId, fieldRepository, context }) {
            const originalGroup = state.form.groups.get(groupId);

            const duplicateCollection = new EntityCollection(
                `/swag-cms-extensions-form-group/${state.form.groups.last().id}/fields`,
                originalGroup.fields.entity,
                originalGroup.fields.context,
                originalGroup.fields.criteria,
            );

            originalGroup.fields.forEach((field) => {
                duplicateCollection.add(duplicateField(state, field, groupId, fieldRepository, context));
            });

            state.form.groups.last().fields = duplicateCollection;
        },

        deleteGroup(state, groupId) {
            const groupToDelete = state.form.groups.get(groupId);

            if (state.activeItem.id === groupId) {
                const nextGroup = state.form.groups.getAt(groupToDelete.position);
                if (nextGroup) {
                    state.activeItem = nextGroup;
                } else {
                    state.activeItem = state.form.groups.getAt(groupToDelete.position - 2);
                }
            }

            sanitizePositions(state.form.groups, groupToDelete.position);
            state.form.groups.remove(groupId);
        },

        deleteField(state, { fieldId, groupId }) {
            const group = state.form.groups.get(groupId);
            const fieldToRemove = group.fields.get(fieldId);

            sanitizePositions(group.fields, fieldToRemove.position);
            group.fields.remove(fieldId);
            state.activeItem = group;
        },

        duplicateField(state, { groupId, fieldId, repository, context }) {
            const group = state.form.groups.get(groupId);
            group.fields.add(duplicateField(state, group.fields.get(fieldId), groupId, repository, context));
        },
    },

    actions: {
        addGroup({ commit, state }, { groupRepository, groupPrefix, fieldRepository, fieldPrefix, context }) {
            commit('addGroup', { repository: groupRepository, prefix: groupPrefix, context });
            commit(
                'addField',
                {
                    groupId: state.form.groups.last().id,
                    repository: fieldRepository,
                    context,
                    prefix: fieldPrefix,
                },
            );
        },

        duplicateGroup({ commit }, { groupId, groupRepository, groupPrefix, fieldRepository, context }) {
            commit('addGroup', { repository: groupRepository, prefix: groupPrefix, context });
            commit('duplicateGroup', { groupId, fieldRepository, context });
        },

        setItemPosition({ commit }, { itemId, to, from, callbackUp, callbackDown }) {
            let steps = 0;

            if (to > from) {
                steps = to - from;
            } else {
                steps = from - to;
            }

            for (let i = 0; i < steps; i += 1) {
                if (to > from) {
                    commit('moveItem', { itemId, callback: callbackDown });
                } else {
                    commit('moveItem', { itemId, callback: callbackUp });
                }
            }
        },

        moveFieldToGroup({ commit, state }, { fieldId, fromGroupId, toGroupId, fieldRepository, context }) {
            const originalFields = state.form.groups.get(fromGroupId).fields;
            const originalField = originalFields.get(fieldId);
            const toggleActiveOnFieldMove = originalField.id === state.activeItem.id;

            const fields = state.form.groups.get(toGroupId).fields;
            const field = duplicateField(state, originalField, fromGroupId, fieldRepository, context);
            field.position = fields.length + 1;
            fields.add(
                field,
            );
            originalFields.remove(fieldId);
            sanitizePositions(originalFields, originalField.position);

            if (toggleActiveOnFieldMove) {
                commit('setActiveItem', fields.last());
            }
        },
    },
};
