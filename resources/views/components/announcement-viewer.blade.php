<div class="modal fade" id="viewAnnouncementModal" tabindex="-1" aria-labelledby="viewAnnouncementLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content annc-modal">
      <div class="annc-hero">
        <h5 id="viewAnnouncementLabel" class="mb-0 text-white text-center"></h5>
        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-0">
        <div class="px-4 pt-3 pb-1">
          <div id="view-meta" class="text-muted small"></div>
        </div>

        <div class="annc-img-wrap">
          <div id="annc-spinner" class="spinner-border" role="status" aria-hidden="true"></div>
          <img id="view-image" alt="" class="annc-img" style="display:none;">
          <div id="view-img-error" class="text-danger small mt-2" style="display:none;"></div>
        </div>

        <div class="px-4 pt-3 pb-4">
          <div id="view-body" class="lh-base"></div>
        </div>
      </div>

      <div class="modal-footer justify-content-center">
        <button class="btn btn-primary px-5" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

@once
@push('styles')
<style>
  .annc-modal{ border-radius:12px; overflow:hidden; }
  .annc-hero{
    background:#0d6efd; /* blue bar like your reference */
    color:#fff; padding:14px 48px; position:relative;
  }

  /* Image stage */
  .annc-img-wrap{
    background:#fff;
    display:flex; align-items:center; justify-content:center;
    min-height: 320px;     /* prevents that thin bar look */
    max-height: 75vh;      /* fit viewport */
    overflow:hidden; border-radius:12px; margin: 0 1.25rem;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
  }
  .annc-img{
    max-width:100%; max-height:75vh; height:auto; width:auto; object-fit:contain;
  }
  #annc-spinner{ display:none; } /* shown via JS while loading */
</style>
@endpush

@push('scripts')
<script>
(function(){
  const modalEl = document.getElementById('viewAnnouncementModal');
  if (!modalEl) return;
  const modal   = new bootstrap.Modal(modalEl);

  const titleEl = document.getElementById('viewAnnouncementLabel');
  const metaEl  = document.getElementById('view-meta');
  const imgEl   = document.getElementById('view-image');
  const errEl   = document.getElementById('view-img-error');
  const bodyEl  = document.getElementById('view-body');
  const spnEl   = document.getElementById('annc-spinner');

  function esc(s){return (s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);}
  function nl2br(s){return esc(s||'').replace(/\n/g,'<br>');}
  function resetImg(){
    imgEl.removeAttribute('src');
    imgEl.style.display='none';
    errEl.style.display='none';
  }

  document.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-view-announcement]');
    if (!a) return;
    e.preventDefault();

    fetch(a.href, { headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }})
      .then(r=>r.ok?r.json():Promise.reject())
      .then(d=>{
        titleEl.textContent = d.title || '';
        metaEl.textContent  = d.published_at ? `Published: ${d.published_at}` : '';
        bodyEl.innerHTML    = nl2br(d.body || '');

        resetImg();
        if (d.image_url) {
          spnEl.style.display='inline-block';
          imgEl.onload  = ()=>{ spnEl.style.display='none'; imgEl.style.display='block'; };
          imgEl.onerror = ()=>{ spnEl.style.display='none'; errEl.textContent='Image failed to load.'; errEl.style.display='block'; };
          imgEl.alt = d.title || 'Announcement image';
          imgEl.src = d.image_url;
        }

        modal.show();
      })
      .catch(()=>{ window.location = a.href; }); // fallback
  });
})();
</script>
@endpush
@endonce

