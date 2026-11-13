const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/fassg/fixed_lists/show.blade.php'), 'utf8');
const script = view.match(/<script id="fixed-list-encoding-script">([\s\S]*?)<\/script>/)[1];

test('academic search initializes once, forbids custom values and links validation feedback', () => {
    const attributes = {};
    const select = { getAttribute: () => 'true' };
    let calls = 0;
    const context = vm.createContext({
        document: { getElementById: id => id === 'fixed-list-course' ? select : null },
        TomSelect: function (element, options) {
            calls++;
            assert.equal(element, select);
            assert.equal(options.create, false);
            select.tomselect = this;
            this.control_input = { setAttribute: (name, value) => { attributes[name] = value; } };
        },
    });
    vm.runInContext(script, context);
    vm.runInContext(script, context);
    assert.equal(calls, 1);
    assert.equal(attributes['aria-describedby'], 'fixed-list-course-error');
    assert.equal(attributes['aria-invalid'], 'true');
});

test('native select remains usable when the existing CDN is unavailable', () => {
    const select = { disabled: false, value: 'Existing official program' };
    vm.runInNewContext(script, { document: { getElementById: () => select } });
    assert.equal(select.disabled, false);
    assert.equal(select.value, 'Existing official program');
    assert.equal(select.tomselect, undefined);
});

test('read-only pages without the encoding form do not initialize a dropdown', () => {
    vm.runInNewContext(script, {
        document: { getElementById: () => null },
        TomSelect: function () { assert.fail('A finalized list has no manual encoding dropdown'); },
    });
});
