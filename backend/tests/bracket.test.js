'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const { nextPowerOfTwo, seedOrder } = require('../dist/src/utils/bracket');

test('nextPowerOfTwo selects the correct bracket size', () => {
  assert.equal(nextPowerOfTwo(2), 2);
  assert.equal(nextPowerOfTwo(6), 8);
  assert.equal(nextPowerOfTwo(9), 16);
});

test('seedOrder distributes high seeds across an eight-slot bracket', () => {
  assert.deepEqual(seedOrder(8), [1, 8, 4, 5, 2, 7, 3, 6]);
});

test('six teams receive two separate byes', () => {
  const teams = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'];
  const slots = seedOrder(nextPowerOfTwo(teams.length)).map((seed) => teams[seed - 1] ?? null);
  const pairs = Array.from({ length: slots.length / 2 }, (_, index) => slots.slice(index * 2, index * 2 + 2));
  assert.equal(pairs.filter((pair) => pair.filter(Boolean).length === 1).length, 2);
});
