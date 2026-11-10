// Interações mínimas: linhas dinâmicas (composição/itens), confirmação de exclusão e campos PF/PJ.
document.addEventListener('click', (ev) => {
  const add = ev.target.closest('[data-add-line]');
  if (add) {
    const box = add.closest('[data-lines]');
    box.querySelector('[data-lines-body]').appendChild(box.querySelector('[data-line-template]').content.cloneNode(true));
    return;
  }
  const remove = ev.target.closest('[data-remove-line]');
  if (remove) remove.closest('.line').remove();
});

document.addEventListener('submit', (ev) => {
  const msg = ev.target.dataset.confirm;
  if (msg && !window.confirm(msg)) ev.preventDefault();
});

const personForm = document.querySelector('[data-person-form]');
if (personForm) {
  const type = personForm.querySelector('[data-person-type]');
  const label = personForm.querySelector('[data-doc-label]');
  const companyOnly = personForm.querySelectorAll('[data-company-only]');
  const sync = () => {
    const company = type.value === 'company';
    label.textContent = company ? 'CNPJ' : 'CPF';
    companyOnly.forEach((el) => { el.hidden = !company; });
  };
  type.addEventListener('change', sync);
  sync();
}
