// Runs the actual inline queue script without Laravel, network requests, or database access.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const template = fs.readFileSync(path.join(__dirname, '../../resources/views/fassg/applications/index.blade.php'), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];

class Element {
    constructor(value = '', dataset = {}) {
        this.value = value;
        this.dataset = dataset;
        this.checked = false;
        this.disabled = false;
        this.hidden = false;
        this.children = [];
        this.listeners = {};
        this.options = [];
        const classes = new Set();
        this.classList = {
            toggle: (name, on) => on ? classes.add(name) : classes.delete(name),
            contains: name => classes.has(name),
        };
    }
    get selectedOptions() { return this.options.filter(option => option.value === this.value); }
    addEventListener(type, handler) { (this.listeners[type] ||= []).push(handler); }
    fire(type = 'change') {
        const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        (this.listeners[type] || []).forEach(handler => handler(event));
        return event;
    }
    replaceChildren() { this.children = []; }
    appendChild(child) { this.children.push(child); }
}

function queue({ noOrdinary = false, program = '1', source = '', open = false, runs = 1 } = {}) {
    const nodes = Object.fromEntries([
        'selectAllApps', 'createBatchForm', 'selectedApplicantsContainer', 'selectedCountNote',
        'batch_program', 'fixed_list_source', 'existing_fixed_list_id', 'batchModeAppend',
        'batch_name', 'selectAllAppsBadge', 'createBatchBtnLabel', 'batchNameGroup',
        'appendListGroup', 'createBatchModal', 'selectedOrdinarySummary',
    ].map(id => [id, new Element()]));
    const submit = new Element();
    nodes.createBatchForm.querySelector = selector => {
        assert.equal(selector, 'button[type="submit"]');
        return submit;
    };
    nodes.batch_program.options = [new Element(), new Element('1', { capacity: '2' }), new Element('2', { capacity: '1' })];
    nodes.batch_program.value = program;
    nodes.createBatchModal.dataset.openOnLoad = open ? '1' : '0';
    nodes.fixed_list_source.options = [new Element(),
        new Element('10', { programId: '1', studentIds: '[1,7]' }), new Element('11', { programId: '1', studentIds: '[8]' }),
        new Element('20', { programId: '2', studentIds: '[9]' })];
    nodes.fixed_list_source.value = source;
    nodes.existing_fixed_list_id.options = [new Element(),
        new Element('30', { programId: '1', studentIds: '[1]' }),
        new Element('31', { programId: '1', studentIds: '[99]' }),
        new Element('40', { programId: '2', studentIds: '[6]' })];
    const applications = Array.from({ length: noOrdinary ? 0 : 6 }, (_, i) => new Element(String(i + 1), {
        studentId: String(i + 1), studentLabel: 'Student ' + (i + 1), programId: i < 5 ? '1' : '2',
    }));
    const document = {
        getElementById: id => noOrdinary && id === 'selectAllApps' ? null : nodes[id],
        createElement: () => new Element(),
        querySelectorAll: selector => ({
            '.app-checkbox': applications, 'input[name="batch_mode"]': [nodes.batchModeAppend],
        })[selector],
    };
    let modalShows = 0;
    const bootstrap = { Modal: { getOrCreateInstance: () => ({ show() {
        modalShows++;
        nodes.createBatchModal.fire('show.bs.modal');
    } }) } };
    const initialize = () => vm.runInNewContext(script, { document, bootstrap });
    for (let i = 0; i < runs; i++) initialize();
    const check = (box, value) => { box.checked = value; box.fire(); };
    const ordinaryIds = () => nodes.selectedApplicantsContainer.children.map(input => input.value);
    return { nodes, applications, submit, check, ordinaryIds, initialize,
        get modalShows() { return modalShows; } };
}

test('ordinary-only selection is explicit and manual replacement updates the payload and count', () => {
    const q = queue();
    assert.deepEqual(q.ordinaryIds(), []);
    q.check(q.applications[0], true);
    q.check(q.applications[1], true);
    assert.deepEqual(q.ordinaryIds(), ['1', '2']);
    assert.equal(q.applications.slice(0, 5).filter(box => !box.disabled).length, 5);
    q.check(q.applications[0], false);
    q.check(q.applications[4], true);
    assert.deepEqual(q.ordinaryIds(), ['2', '5']);
    assert.match(q.nodes.selectedCountNote.textContent, /^2 student\(s\) selected; 2 in/);
    assert.equal(q.submit.disabled, false);
    q.nodes.createBatchModal.fire('show.bs.modal');
    assert.deepEqual(q.ordinaryIds(), ['2', '5']);
    assert.equal(q.applications[0].checked, false);
});

test('overflow disables submission without hiding or rejecting applicants', () => {
    const q = queue();
    q.check(q.applications[0], true);
    q.check(q.applications[1], true);
    q.check(q.applications[2], true);
    assert.equal(q.submit.disabled, true);
    assert.equal(q.nodes.createBatchForm.fire('submit').defaultPrevented, true);
    assert.match(q.nodes.selectedCountNote.textContent, /Uncheck ordinary applicants/);
    q.check(q.applications[0], false);
    assert.equal(q.submit.disabled, false);
});

test('program changes clear the foreign source and do not preselect ordinary applicants', () => {
    const q = queue({ source: '10' });
    assert.equal(q.nodes.fixed_list_source.options[3].disabled, true);
    q.nodes.batch_program.value = '2';
    q.nodes.batch_program.fire();
    assert.deepEqual(q.ordinaryIds(), []);
    assert.equal(q.nodes.fixed_list_source.value, '');
    assert.equal(q.nodes.fixed_list_source.options[1].hidden, true);
    assert.equal(q.nodes.fixed_list_source.options[3].hidden, false);
    assert.equal(q.submit.disabled, true);
});

test('choosing a finalized list includes its entire roster and switching replaces the source', () => {
    const q = queue();
    assert.equal(q.submit.disabled, true);
    q.nodes.fixed_list_source.value = '10';
    q.nodes.fixed_list_source.fire();
    assert.match(q.nodes.selectedCountNote.textContent, /^2 student/);
    assert.equal(q.submit.disabled, false);
    q.nodes.fixed_list_source.value = '11';
    q.nodes.fixed_list_source.fire();
    assert.match(q.nodes.selectedCountNote.textContent, /^1 student/);
    assert.deepEqual(q.ordinaryIds(), []);
    q.nodes.fixed_list_source.value = '';
    q.nodes.fixed_list_source.fire();
    assert.equal(q.submit.disabled, true);
});

test('mixed selection deduplicates overlapping students without unchecking ordinary choices', () => {
    const q = queue({ source: '10' });
    q.check(q.applications[0], true);
    assert.deepEqual(q.ordinaryIds(), ['1']);
    assert.match(q.nodes.selectedCountNote.textContent, /^2 student/);
    assert.match(q.nodes.selectedCountNote.textContent, /1 overlapping student/);
    assert.equal(q.submit.disabled, false);
    q.check(q.applications[1], true);
    assert.match(q.nodes.selectedCountNote.textContent, /^3 student/);
    assert.equal(q.submit.disabled, true);
    assert.deepEqual(q.ordinaryIds(), ['1', '2']);
});

test('appending counts unique existing members and prevents combined-capacity overflow', () => {
    const q = queue();
    q.check(q.applications[0], true);
    q.check(q.applications[1], true);
    q.nodes.existing_fixed_list_id.value = '30';
    q.nodes.batchModeAppend.checked = true;
    q.nodes.batchModeAppend.fire();
    assert.equal(q.submit.disabled, false);
    assert.match(q.nodes.selectedCountNote.textContent, /2 in the resulting draft/);
    q.nodes.existing_fixed_list_id.value = '31';
    q.nodes.existing_fixed_list_id.fire();
    assert.equal(q.submit.disabled, true);
    assert.match(q.nodes.selectedCountNote.textContent, /3 in the resulting draft/);
});

test('fixed-only batches work without ordinary rows and never post member IDs', () => {
    const layout = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/app.blade.php'), 'utf8');
    assert.equal((layout.match(/@stack\('scripts'\)/g) || []).length, 1);
    assert.doesNotMatch(template, /fixed-item-checkbox|name="selected_fixed_list_items/);
    assert.match(template, /name="fixed_list_source"/);
    const q = queue({ noOrdinary: true, program: '', runs: 2 });
    q.nodes.batch_program.value = '1';
    q.nodes.batch_program.fire();
    q.nodes.fixed_list_source.value = '10';
    q.nodes.fixed_list_source.fire();
    assert.deepEqual(q.ordinaryIds(), []);
    assert.equal(q.submit.disabled, false);
    assert.equal(q.nodes.createBatchForm.fire('submit').defaultPrevented, false);
    assert.equal(q.nodes.batch_program.listeners.change.length, 1);
});

test('source-list entry opens once with the full roster and leaves ordinary applicants unchecked', () => {
    const q = queue({ program: '1', source: '10', open: true, runs: 2 });
    assert.equal(q.modalShows, 1);
    assert.equal(q.nodes.fixed_list_source.value, '10');
    assert.equal(q.applications.some(box => box.checked), false);
    assert.equal(q.submit.disabled, false);
    assert.match(q.nodes.selectedCountNote.textContent, /^2 student/);
});

test('ordinary-only, fixed-only and mixed empty selections cannot submit', () => {
    const q = queue();
    assert.equal(q.nodes.createBatchForm.fire('submit').defaultPrevented, true);
    q.nodes.fixed_list_source.value = '11';
    q.nodes.fixed_list_source.fire();
    q.check(q.applications[1], true);
    assert.match(q.nodes.selectedCountNote.textContent, /^2 student/);
    assert.equal(q.nodes.createBatchForm.fire('submit').defaultPrevented, false);
    q.check(q.applications[1], false);
    q.nodes.fixed_list_source.value = '';
    q.nodes.fixed_list_source.fire();
    assert.equal(q.submit.disabled, true);
});

test('an oversized or empty fixed roster is never truncated or implicitly filled', () => {
    const q = queue({ noOrdinary: true });
    q.nodes.fixed_list_source.options[1].dataset.studentIds = '[1,2,3]';
    q.nodes.fixed_list_source.value = '10';
    q.nodes.fixed_list_source.fire();
    assert.match(q.nodes.selectedCountNote.textContent, /^3 student/);
    assert.equal(q.submit.disabled, true);
    q.nodes.fixed_list_source.options[1].dataset.studentIds = '[]';
    q.nodes.fixed_list_source.fire();
    assert.equal(q.submit.disabled, true);
});
