import { writable } from 'svelte/store';

function createStore() {
  const { subscribe, set, update } = writable([]);

  return {
    subscribe,
    update,
    set,
    reset: () => set([])
  };
}

const tagsStore = createStore();

export {tagsStore}
