import { ref } from 'vue'

/**
 * Native HTML5 drag-and-drop reordering for a ref([]) list.
 * Bind on each item:
 *   draggable="true"
 *   @dragstart="d.onDragStart(i, $event)"  @dragover="d.onDragOver(i, $event)"
 *   @drop="d.onDrop(i, $event)"            @dragend="d.onDragEnd"
 * `dragIndex` / `overIndex` drive the visual feedback classes.
 */
export function useDragReorder(list) {
  const dragIndex = ref(null)
  const overIndex = ref(null)

  const reset = () => { dragIndex.value = null; overIndex.value = null }

  const onDragStart = (i, e) => {
    dragIndex.value = i
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(i)) // Firefox needs data to start a drag
  }

  const onDragOver = (i, e) => {
    e.preventDefault() // required to allow dropping
    e.dataTransfer.dropEffect = 'move'
    overIndex.value = i
  }

  const onDrop = (i, e) => {
    e.preventDefault()
    const from = dragIndex.value
    if (from === null || from === i) return reset()
    const [moved] = list.value.splice(from, 1)
    list.value.splice(i, 0, moved)
    reset()
  }

  const onDragEnd = () => reset()

  return { dragIndex, overIndex, onDragStart, onDragOver, onDrop, onDragEnd }
}
