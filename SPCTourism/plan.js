document.addEventListener('DOMContentLoaded', () => {
  /* ── Tab switching ── */
  const tabBtns   = document.querySelectorAll('.tab-nav-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
      tabPanels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });

  /* ── Transport card → map panel ── */
  const transportCards = document.querySelectorAll('.transport-card');
  const mapPanels = {
    bus:     document.getElementById('map-bus'),
    car:     document.getElementById('map-car'),
    jeepney: document.getElementById('map-jeepney')
  };

  transportCards.forEach(card => {
    card.addEventListener('click', () => {
      const type = card.dataset.transport;
      transportCards.forEach(c => c.classList.remove('active'));
      Object.values(mapPanels).forEach(p => p.classList.remove('active'));
      card.classList.add('active');
      mapPanels[type].classList.add('active');
      setTimeout(() => {
        mapPanels[type].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }, 50);
    });
  });

  /* ── Close map buttons ── */
  ['bus', 'car', 'jeepney'].forEach(type => {
    document.getElementById('close-' + type).addEventListener('click', () => {
      mapPanels[type].classList.remove('active');
      transportCards.forEach(c => c.classList.remove('active'));
      document.querySelector('.transport-selector').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
});
