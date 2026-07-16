document.addEventListener('DOMContentLoaded', function () {

  //FUNZIONI DI SUPPORTO
  function isPositiveMoney(value) {
    const re = /^\d+(\.\d{1,2})?$/;
    return re.test(value) && parseFloat(value) > 0;
  }

  function isPositiveInt(value) {
    const re = /^[0-9]+$/;
    return re.test(value) && parseInt(value, 10) > 0;
  }

  function setFormError(form, htmlMsg) {
    const section = form.closest('section') || document;
    const div = section.querySelector('.form_errors');
    if (div) div.innerHTML = htmlMsg;
  }

  function clearFormError(form) {
    setFormError(form, '');
  }

  //AGGIUNGI
  function validateFormAggiungiProdotto() {
    const form = document.querySelector('form.admin-form input[name="azione"][value="aggiungi"]')?.closest('form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
      clearFormError(form);

      let ok = true;
      let msg = '';

      const nome = form.querySelector('#nome')?.value.trim() ?? '';
      const descrizione = form.querySelector('#descrizione')?.value.trim() ?? '';
      const materiali = form.querySelector('#materiali')?.value.trim() ?? '';
      const guidaTaglie = form.querySelector('#guida_taglie')?.value.trim() ?? '';
      const cura = form.querySelector('#cura')?.value.trim() ?? '';
      const prezzo = form.querySelector('#prezzo')?.value.trim() ?? '';
      const categoria = form.querySelector('#categoria')?.value ?? '';
      const target = form.querySelector('#target')?.value ?? '';

      if (nome === '' || descrizione === '' || materiali === '' || guidaTaglie === '' || cura === '' || prezzo === '' || categoria === '' || target === '') {
        ok = false;
        msg += '<p>Compila tutti i campi obbligatori.</p>';
      }

      if (prezzo !== '' && !isPositiveMoney(prezzo)) {
        ok = false;
        msg += '<p>Prezzo non valido: inserisci un valore maggiore di 0 (max 2 decimali, usa il punto).</p>';
      }

      if (categoria !== '' && !isPositiveInt(categoria)) {
        ok = false;
        msg += '<p>Categoria non valida. Seleziona una categoria dalla lista.</p>';
      }

      if (target !== '' && !['uomo', 'donna', 'bambino', 'bambina'].includes(target)) {
        ok = false;
        msg += '<p>Target non valido. Seleziona un target dalla lista.</p>';
      }

      if (!ok) {
        setFormError(form, msg);
        event.preventDefault();
      }
    });
  }

  //MODIFICA
  function validateFormModificaProdotto() {
    const form = document.querySelector('form.admin-form input[name="azione"][value="modifica"]')?.closest('form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
      clearFormError(form);

      let ok = true;
      let msg = '';

      const idProd = form.querySelector('#prodotto_mod')?.value ?? '';
      const nomeMod = form.querySelector('#nome_mod')?.value.trim() ?? '';
      const descMod = form.querySelector('#descrizione_mod')?.value.trim() ?? '';
      const materialiMod = form.querySelector('#materiali_mod')?.value.trim() ?? '';
      const guidaMod = form.querySelector('#guida_taglie_mod')?.value.trim() ?? '';
      const curaMod = form.querySelector('#cura_mod')?.value.trim() ?? '';
      const prezzoMod = form.querySelector('#prezzo_mod')?.value.trim() ?? '';

      if (idProd === '' || !isPositiveInt(idProd)) {
        ok = false;
        msg += '<p>Seleziona un prodotto valido da modificare.</p>';
      }

      if (nomeMod === '' && descMod === '' && materialiMod === '' && guidaMod === '' && curaMod === '' && prezzoMod === '') {
        ok = false;
        msg += '<p>Modifica almeno un campo.</p>';
      }

      if (prezzoMod !== '' && !isPositiveMoney(prezzoMod)) {
        ok = false;
        msg += '<p>Nuovo prezzo non valido: inserisci un valore maggiore di 0 (max 2 decimali, usa il punto).</p>';
      }

      if (!ok) {
        setFormError(form, msg);
        event.preventDefault();
      }
    });
  }


  //STATO 
  function validateFormStatoProdotto() {
    const form = document.querySelector('form.admin-form input[name="azione"][value="stato"]')?.closest('form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
      clearFormError(form);

      let ok = true;
      let msg = '';

      const idProd = form.querySelector('#prodotto_stato')?.value ?? '';
      const stato = form.querySelector('#stato')?.value ?? '';

      if (idProd === '' || !isPositiveInt(idProd)) {
        ok = false;
        msg += '<p>Seleziona un prodotto valido.</p>';
      }

      if (!(stato === '0' || stato === '1')) {
        ok = false;
        msg += '<p>Seleziona uno stato valido (Attivo/Disattivo).</p>';
      }

      if (!ok) {
        setFormError(form, msg);
        event.preventDefault();
      }
    });
  }

  // ELIMINA
  function validateFormEliminaProdotto() {
    const form = document.querySelector('form.admin-form input[name="azione"][value="elimina"]')?.closest('form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
      clearFormError(form);

      let ok = true;
      let msg = '';

      const idProd = form.querySelector('#prodotto_del')?.value ?? '';

      if (idProd === '' || !isPositiveInt(idProd)) {
        ok = false;
        msg += '<p>Seleziona un prodotto valido da eliminare.</p>';
      }

      if (!ok) {
        setFormError(form, msg);
        event.preventDefault();
        return;
      }

      const sure = confirm('Confermi l\'eliminazione definitiva del prodotto? Se è presente negli ordini, non sarà eliminabile.');
      if (!sure) {
        event.preventDefault();
      }
    });
  }

  validateFormAggiungiProdotto();
  validateFormModificaProdotto();
  validateFormStatoProdotto();
  validateFormEliminaProdotto();

});
