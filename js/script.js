document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav = document.querySelector('.main-nav');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            const isOpen = mainNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', String(isOpen));
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-booking-form]').forEach((form) => {
        const fieldSelect = form.querySelector('[data-field-select]');
        const durationSelect = form.querySelector('[data-duration-select]');
        const timeSelect = form.querySelector('[data-time-select]');
        const totalPreview = form.querySelector('[data-total-preview]');
        const dateInput = form.querySelector('[data-booking-date], input[name="tanggal"]');
        const availabilityUrl = form.dataset.availabilityUrl;
        const availabilitySummary = form.querySelector('[data-availability-summary]');
        const availabilitySlots = form.querySelector('[data-availability-slots]');
        if (!fieldSelect || !durationSelect || !timeSelect || !totalPreview) return;

        const formatRupiah = (amount) => `Rp ${new Intl.NumberFormat('id-ID').format(amount)}`;
        const slotOptions = Array.from(timeSelect.options).filter((option) => option.dataset.slotTime);
        let currentSlots = new Map();
        let availabilityRequest;

        const updateTotal = () => {
            const option = fieldSelect.options[fieldSelect.selectedIndex];
            const price = Number(option?.dataset.price || 0);
            const duration = Number(durationSelect.value || 0);
            totalPreview.textContent = formatRupiah(price * duration);
        };

        const updateSelectedChip = () => {
            if (!availabilitySlots) return;
            availabilitySlots.querySelectorAll('.slot-chip').forEach((button) => {
                const isSelected = button.dataset.time === timeSelect.value;
                button.classList.toggle('is-selected', isSelected);
                button.setAttribute('aria-pressed', String(isSelected));
            });
        };

        const showAvailability = async () => {
            const fieldId = fieldSelect.value;
            const date = dateInput?.value || '';
            const duration = durationSelect.value;

            if (!fieldId || !date || !availabilityUrl) {
                currentSlots.clear();
                slotOptions.forEach((option) => {
                    option.disabled = false;
                    option.textContent = option.dataset.slotTime;
                });
                if (availabilitySummary) {
                    availabilitySummary.textContent = 'Pilih lapangan dan tanggal untuk melihat jam yang tersedia.';
                }
                if (availabilitySlots) availabilitySlots.replaceChildren();
                updateSelectedChip();
                return;
            }

            if (availabilityRequest) availabilityRequest.abort();
            availabilityRequest = new AbortController();
            if (availabilitySummary) availabilitySummary.textContent = 'Memeriksa jadwal...';
            if (availabilitySlots) availabilitySlots.replaceChildren();

            const params = new URLSearchParams({
                lapangan_id: fieldId,
                tanggal: date,
                durasi_jam: duration,
            });
            if (form.dataset.bookingExclude) {
                params.set('exclude_booking_id', form.dataset.bookingExclude);
            }

            try {
                const response = await fetch(`${availabilityUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                    signal: availabilityRequest.signal,
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Jadwal belum dapat diperiksa.');
                }

                currentSlots = new Map(data.slots.map((slot) => [slot.time, slot.available]));
                slotOptions.forEach((option) => {
                    const available = currentSlots.get(option.dataset.slotTime) === true;
                    option.disabled = !available;
                    option.textContent = `${option.dataset.slotTime} · ${available ? 'Tersedia' : 'Tidak tersedia'}`;
                });

                if (currentSlots.get(timeSelect.value) === false) {
                    timeSelect.value = '';
                }

                if (availabilitySlots) {
                    const fragment = document.createDocumentFragment();
                    data.slots.forEach((slot) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'slot-chip';
                        button.dataset.time = slot.time;
                        button.textContent = slot.time;
                        button.disabled = !slot.available;
                        button.setAttribute('aria-label', `${slot.time}, ${slot.available ? 'tersedia' : 'tidak tersedia'}`);
                        button.setAttribute('aria-pressed', String(slot.time === timeSelect.value));
                        if (slot.time === timeSelect.value) button.classList.add('is-selected');
                        button.addEventListener('click', () => {
                            timeSelect.value = slot.time;
                            updateSelectedChip();
                        });
                        fragment.append(button);
                    });
                    availabilitySlots.replaceChildren(fragment);
                }

                const jumlahTersedia = data.slots.filter((slot) => slot.available).length;
                if (availabilitySummary) {
                    availabilitySummary.textContent = `${jumlahTersedia} jam mulai tersedia untuk durasi ${data.duration} jam. Jam yang terisi dan jam yang sudah lewat tidak bisa dipilih.`;
                }
            } catch (error) {
                if (error.name === 'AbortError') return;
                currentSlots.clear();
                slotOptions.forEach((option) => {
                    option.disabled = false;
                    option.textContent = option.dataset.slotTime;
                });
                if (availabilitySummary) {
                    availabilitySummary.textContent = error.message || 'Jadwal tidak bisa dimuat. Silakan muat ulang halaman.';
                }
                if (availabilitySlots) availabilitySlots.replaceChildren();
            }
        };

        fieldSelect.addEventListener('change', updateTotal);
        fieldSelect.addEventListener('change', showAvailability);
        durationSelect.addEventListener('change', () => {
            updateTotal();
            showAvailability();
        });
        dateInput?.addEventListener('change', showAvailability);
        timeSelect.addEventListener('change', updateSelectedChip);
        updateTotal();
        showAvailability();
    });
});
