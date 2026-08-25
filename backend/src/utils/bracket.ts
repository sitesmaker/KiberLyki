export function nextPowerOfTwo(value: number) {
  let result = 1;
  while (result < value) result *= 2;
  return result;
}

export function seedOrder(size: number) {
  if (size < 2 || (size & (size - 1)) !== 0) {
    throw new Error('Bracket size must be a power of two and at least 2');
  }

  let order = [1, 2];
  while (order.length < size) {
    const sum = order.length * 2 + 1;
    order = order.flatMap((seed) => [seed, sum - seed]);
  }
  return order;
}
