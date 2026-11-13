// Exercise the actual Blade script without requests, Laravel or database access.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const template = fs.readFileSync(path.join(__dirname, '../../resources/views/fassg/fixed_lists/show.blade.php'), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];

function setup(confirmed = true) {
    const element = (disabled = false) => ({
        disabled, checked: false, listeners: {},
        addEventListener(type, listener) { this.listeners[type] = listener; },
        fire(type) {
            const event = { prevented: false, preventDefault() { this.prevented = true; } };
            this.listeners[type]?.(event);
            return event;
        },
    });
    const nodes = { selectAllCheckbox: element(), bulkEndorseBtn: element(), bulkEndorseForm: element() };
    const boxes = [element(), element(), element(true)];
    const confirmations = [];
    const document = { getElementById: id => nodes[id], querySelectorAll: () => boxes };
    vm.runInNewContext(script, { document, confirm: text => { confirmations.push(text); return confirmed; } });
    return { nodes, boxes, confirmations };
}

test('bulk endorsement submits once without timers or individual form navigation', () => {
    const { nodes, boxes, confirmations } = setup();
    nodes.selectAllCheckbox.checked = true;
    nodes.selectAllCheckbox.fire('change');
    assert.deepEqual(boxes.map(box => box.checked), [true, true, false]);
    assert.equal(nodes.bulkEndorseForm.fire('submit').prevented, false);
    assert.equal(confirmations.length, 1);
    assert.match(confirmations[0], /Endorse 2 selected student/);
    assert.doesNotMatch(confirmations[0], /outside-criteria|overrides/i);
    assert.match(confirmations[0], /Sponsor approval remains separate/);
    assert.equal(nodes.bulkEndorseBtn.disabled, true);
    assert.equal(nodes.bulkEndorseForm.fire('submit').prevented, true);
    assert.equal(confirmations.length, 1);
});

test('empty and cancelled selections send nothing and remain editable', () => {
    const { nodes, boxes, confirmations } = setup(false);
    assert.equal(nodes.bulkEndorseBtn.disabled, true);
    assert.equal(nodes.bulkEndorseForm.fire('submit').prevented, true);
    assert.equal(confirmations.length, 0);
    boxes[0].checked = true;
    boxes[0].fire('change');
    assert.equal(nodes.selectAllCheckbox.indeterminate, true);
    assert.equal(nodes.bulkEndorseForm.fire('submit').prevented, true);
    assert.equal(nodes.bulkEndorseBtn.disabled, false);
    boxes[0].checked = false;
    boxes[0].fire('change');
    assert.equal(nodes.bulkEndorseBtn.disabled, true);
});
