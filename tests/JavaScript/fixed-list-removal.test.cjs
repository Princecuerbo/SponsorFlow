// Runs the actual modal script with DOM/Bootstrap lifecycle doubles; no HTTP or database.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const template = fs.readFileSync(path.join(__dirname, '../../resources/views/fassg/fixed_lists/show.blade.php'), 'utf8');
const script = template.match(/<script id="fixed-list-removal-script">([\s\S]*?)<\/script>/)[1];

function setup({ absent = false, runs = 1 } = {}) {
    class Element {
        constructor() {
            this.dataset = {};
            this.listeners = {};
            this.attributes = {};
            this.disabled = false;
            this.isConnected = true;
            this.textContent = '';
        }
        addEventListener(type, handler) { (this.listeners[type] ||= []).push(handler); }
        fire(type, properties = {}) {
            const event = { ...properties, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
            (this.listeners[type] || []).forEach(handler => handler(event));
            return event;
        }
        focus() { focused = this; }
        setAttribute(key, value) { this.attributes[key] = value; }
    }
    let focused = null;
    const ids = ['removeStudentModal', 'confirmRemoveStudent', 'cancelRemoveStudent', 'removeStudentIdentity', 'removeStudentProgress', 'removeStudentError'];
    const nodes = Object.fromEntries(ids.map(id => [id, new Element()]));
    const close = new Element();
    const modal = nodes.removeStudentModal;
    modal.querySelectorAll = () => [nodes.cancelRemoveStudent, close];
    let visible = false;
    const forms = ['First Student', 'Second <student> & "name"'].map((name, index) => {
        const form = new Element();
        form.dataset = { studentName: name, studentId: '2024-1000' + index };
        form.submissions = 0;
        form.action = '/fassg/fixed-lists/1/items/' + (index + 1);
        form.method = 'POST';
        form.fields = { _token: 'unchanged-token', _method: 'DELETE' };
        const trigger = new Element();
        trigger.closest = () => form;
        form.trigger = trigger;
        form.querySelector = () => trigger;
        form.requestSubmit = () => {
            if (form.failSubmission) throw new Error('Simulated browser submission failure');
            if (!form.fire('submit').defaultPrevented) form.submissions++;
        };
        return form;
    });
    const document = {
        getElementById: id => absent && id === 'removeStudentModal' ? null : nodes[id],
        querySelectorAll: () => forms,
    };
    const window = new Element();
    const initialize = () => vm.runInNewContext(script, { document, window });
    for (let i = 0; i < runs; i++) initialize();
    const open = (index = 0) => {
        const event = modal.fire('show.bs.modal', { relatedTarget: forms[index].trigger });
        if (!event.defaultPrevented) { visible = true; modal.fire('shown.bs.modal'); }
        return event;
    };
    // Bootstrap emits this same hide/hidden lifecycle for Cancel, Close, backdrop and Escape.
    const dismiss = () => {
        const event = modal.fire('hide.bs.modal');
        if (!event.defaultPrevented) { visible = false; modal.fire('hidden.bs.modal'); }
        return event;
    };
    return { nodes, modal, forms, close, window, open, dismiss, initialize,
        confirm: () => nodes.confirmRemoveStudent.fire('click'),
        get focused() { return focused; }, get visible() { return visible; } };
}

test('opening each row displays its own identity as text without submitting', () => {
    const q = setup();
    q.open();
    assert.equal(q.nodes.removeStudentIdentity.textContent, 'First Student (2024-10000)');
    assert.equal(q.focused, q.nodes.cancelRemoveStudent);
    assert.equal(q.forms[0].submissions, 0);
    q.dismiss();
    assert.equal(q.focused, q.forms[0].trigger);
    q.open(1);
    assert.equal(q.nodes.removeStudentIdentity.textContent, 'Second <student> & "name" (2024-10001)');
    assert.equal(q.nodes.removeStudentIdentity.innerHTML, undefined);
    assert.equal(q.forms[1].submissions, 0);
});

test('Bootstrap Cancel, Close and Escape dismissal lifecycle does not submit', () => {
    assert.match(template, /data-bs-keyboard="true" data-bs-backdrop="true"/);
    assert.match(template, /id="cancelRemoveStudent"[^>]+data-bs-dismiss="modal"/);
    assert.match(template, /data-bs-dismiss="modal" aria-label="Close removal confirmation"/);
    for (const reason of ['Cancel', 'Close', 'Escape']) {
        const q = setup();
        q.open();
        assert.equal(q.dismiss().defaultPrevented, false, reason);
        q.confirm();
        assert.equal(q.visible, false);
        assert.equal(q.forms[0].submissions, 0);
        assert.equal(q.nodes.confirmRemoveStudent.disabled, true);
        assert.equal(q.focused, q.forms[0].trigger);
    }
});

test('confirmation submits only the selected original form once and shows processing', () => {
    const q = setup();
    q.open(1);
    q.confirm();
    q.confirm();
    q.forms[1].requestSubmit();
    assert.equal(q.forms[0].submissions, 0);
    assert.equal(q.forms[1].submissions, 1);
    assert.equal(q.forms[1].action, '/fassg/fixed-lists/1/items/2');
    assert.equal(q.forms[1].method, 'POST');
    assert.deepEqual(q.forms[1].fields, { _token: 'unchanged-token', _method: 'DELETE' });
    assert.equal(q.nodes.confirmRemoveStudent.textContent, 'Removing...');
    assert.equal(q.modal.attributes['aria-busy'], 'true');
    assert.equal(q.nodes.cancelRemoveStudent.disabled, true);
    assert.equal(q.close.disabled, true);
    assert.equal(q.open().defaultPrevented, true);
    assert.equal(q.dismiss().defaultPrevented, true);
});

test('unconfirmed submissions and unrelated modal triggers are blocked', () => {
    const q = setup();
    q.forms[0].requestSubmit();
    assert.equal(q.forms[0].submissions, 0);
    assert.equal(q.modal.fire('show.bs.modal', {}).defaultPrevented, true);
    q.open();
    q.forms[1].requestSubmit();
    assert.equal(q.forms[1].submissions, 0);
});

test('submission exceptions show a recoverable error without deleting a row', () => {
    const q = setup();
    q.open();
    q.forms[0].failSubmission = true;
    q.confirm();
    assert.equal(q.nodes.removeStudentError.hidden, false);
    assert.match(q.nodes.removeStudentError.textContent, /could not be submitted/);
    assert.equal(q.nodes.confirmRemoveStudent.disabled, false);
    assert.equal(q.nodes.cancelRemoveStudent.disabled, false);
    assert.equal(q.forms[0].submissions, 0);
    q.forms[0].failSubmission = false;
    q.confirm();
    assert.equal(q.forms[0].submissions, 1);
});

test('initialization is idempotent and read-only pages do not install handlers', () => {
    const q = setup({ runs: 2 });
    assert.equal(q.modal.listeners['show.bs.modal'].length, 1);
    assert.equal(q.nodes.confirmRemoveStudent.listeners.click.length, 1);
    q.open();
    q.initialize();
    q.confirm();
    assert.equal(q.forms[0].submissions, 1);
    const readonly = setup({ absent: true });
    assert.deepEqual(readonly.modal.listeners, {});
    assert.deepEqual(readonly.forms[0].listeners, {});
});

test('back-forward cache restoration releases processing controls', () => {
    const q = setup();
    q.open();
    q.confirm();
    q.window.fire('pageshow', { persisted: true });
    assert.equal(q.nodes.confirmRemoveStudent.disabled, false);
    assert.equal(q.nodes.removeStudentProgress.textContent, '');
    assert.equal(q.dismiss().defaultPrevented, false);
});

test('removal keeps its authorized POST/DELETE form, CSRF and server feedback path', () => {
    const form = template.match(/@if \(\$canRemove\)([\s\S]*?)@endif/)[1];
    assert.match(form, /method="POST" action="{{ route\('fassg.fixed-lists.items.destroy', \[\$list, \$item\]\) }}/);
    assert.match(form, /@csrf @method\('DELETE'\)/);
    assert.match(form, /button type="button"/);
    assert.doesNotMatch(form, /confirm\(/);
    assert.match(template, /\$canRemove = \$canEditStudents && \$list->status === \\App\\Enums\\FixedListStatus::Draft/);
    assert.match(template, /@if \(\$canEditStudents && \$list->status === \\App\\Enums\\FixedListStatus::Draft\)/);
    assert.match(template, /@if \(session\('status'\)\)/);
    assert.match(template, /aria-describedby="removeStudentModalMessage removeStudentModalInfo"/);
    const layout = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/app.blade.php'), 'utf8');
    const flash = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/_flash.blade.php'), 'utf8');
    assert.match(layout, /@include\('partials._flash'\)/);
    assert.match(flash, /\$errors->all\(\)/);
    assert.doesNotMatch(script, /fetch\(|\.remove\(|innerHTML|\.action\s*=/);
});
