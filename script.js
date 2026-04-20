function initCarousel() {
    const container = document.getElementById('carousel-container');
    if (!container) return;

    let currentIndex = 0;
    
    carouselSlides.forEach((slide, index) => {
        const slideDiv = document.createElement('div');
        slideDiv.className = `carousel-slide ${index === 0 ? 'active' : ''}`;
        slideDiv.style.backgroundImage = `url('${slide.image}')`;
        slideDiv.innerHTML = `
            <div class="carousel-content">
                <a href="${slide.link}">${slide.text}</a>
            </div>
        `;
        container.appendChild(slideDiv);
    });

    const slides = document.querySelectorAll('.carousel-slide');
    
    function showSlide(index) {
        slides.forEach(s => s.classList.remove('active'));
        slides[index].classList.add('active');
    }
    
    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        showSlide(currentIndex);
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(currentIndex);
    }

    let interval = setInterval(nextSlide, 3000);

    document.getElementById('carousel-next')?.addEventListener('click', () => {
        clearInterval(interval);
        nextSlide();
        interval = setInterval(nextSlide, 3000);
    });

    document.getElementById('carousel-prev')?.addEventListener('click', () => {
        clearInterval(interval);
        prevSlide();
        interval = setInterval(nextSlide, 3000);
    });
}

function renderClassicTable() {
    const tableContainer = document.getElementById('classic-table-container');
    if (!tableContainer) return;

    let sortCol = null;
    let sortAsc = true;
    let currentData = [...echipamente];

    function render() {
        let html = '<table class="sortable-table"><thead><tr>';
        const cols = [
            { key: 'id', label: 'ID' },
            { key: 'nume', label: 'Nume Echipament' },
            { key: 'tip', label: 'Tip' },
            { key: 'putere', label: 'Putere' },
            { key: 'cost', label: 'Cost (Aur)' }
        ];

        cols.forEach(col => {
            let sortClass = '';
            if (sortCol === col.key) {
                sortClass = sortAsc ? 'sorted-asc' : 'sorted-desc';
            }
            html += `<th data-key="${col.key}" class="sortable ${sortClass}">${col.label}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        currentData.forEach(row => {
            html += `<tr>
                <td>${row.id}</td>
                <td>${row.nume}</td>
                <td>${row.tip}</td>
                <td>${row.putere}</td>
                <td>${row.cost}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        tableContainer.innerHTML = html;

        tableContainer.querySelectorAll('th.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const key = th.getAttribute('data-key');
                if (sortCol === key) {
                    sortAsc = !sortAsc;
                } else {
                    sortCol = key;
                    sortAsc = true;
                }
                
                currentData.sort((a, b) => {
                    let valA = a[key];
                    let valB = b[key];
                    if (typeof valA === 'string') {
                        valA = valA.toLowerCase();
                        valB = valB.toLowerCase();
                    }
                    if (valA < valB) return sortAsc ? -1 : 1;
                    if (valA > valB) return sortAsc ? 1 : -1;
                    return 0;
                });
                render();
            });
        });
    }
    render();
}

function renderVerticalTable() {
    const tContainer = document.getElementById('vertical-table-container');
    if (!tContainer) return;

    let sortIndex = null;
    let sortAsc = true;
    
    function render() {
        let html = '<table class="vertical-table">';
        
        const keys = Object.keys(statisticiJucatori[0]).filter(k => k.startsWith('val'));
        
        statisticiJucatori.forEach((row, rowIndex) => {
            html += '<tr>';
            let sortClass = '';
            if (sortIndex === rowIndex) {
                sortClass = sortAsc ? 'sorted-asc' : 'sorted-desc';
            }
            
            html += `<th class="v-sortable ${sortClass}" data-index="${rowIndex}">${row.camp}</th>`;
            
            keys.forEach(k => {
                html += `<td>${row[k]}</td>`;
            });
            html += '</tr>';
        });
        html += '</table>';
        tContainer.innerHTML = html;

        tContainer.querySelectorAll('th.v-sortable').forEach(th => {
            th.addEventListener('click', () => {
                const index = parseInt(th.getAttribute('data-index'));
                if (sortIndex === index) {
                    sortAsc = !sortAsc;
                } else {
                    sortIndex = index;
                    sortAsc = true;
                }

                const sortRow = statisticiJucatori[index];
                
                keys.sort((k1, k2) => {
                    let v1 = sortRow[k1];
                    let v2 = sortRow[k2];
                    if (typeof v1 === 'string') v1 = v1.toLowerCase();
                    if (typeof v2 === 'string') v2 = v2.toLowerCase();
                    
                    if (v1 < v2) return sortAsc ? -1 : 1;
                    if (v1 > v2) return sortAsc ? 1 : -1;
                    return 0;
                });

                const newStats = statisticiJucatori.map(r => {
                    let newRow = { camp: r.camp };
                    keys.forEach((k, i) => {
                        newRow[k] = r[k];
                    });
                    return newRow;
                });
                
                for(let i=0; i<statisticiJucatori.length; i++) {
                    statisticiJucatori[i] = newStats[i];
                }
                
                render();
            });
        });
    }
    render();
}

function initCollapsibleLists() {
    const listItems = document.querySelectorAll('.collapsible-list > li, .collapsible-list li');
    
    listItems.forEach(li => {
        const subList = li.querySelector('ul, ol');
        if (subList) {
            li.classList.add('has-children');
            
            const textNode = Array.from(li.childNodes).find(n => n.nodeType === 3 && n.textContent.trim().length > 0);
            if (textNode) {
                const span = document.createElement('span');
                span.className = 'toggle-btn';
                span.textContent = textNode.textContent;
                li.insertBefore(span, textNode);
                li.removeChild(textNode);
                
                span.addEventListener('click', (e) => {
                    e.stopPropagation();
                    li.classList.toggle('expanded');
                });
            }
        }
    });
}

function initFormDependencies() {
    const judetSelect = document.getElementById('judet');
    const locSelect = document.getElementById('localitate');
    
    if (judetSelect && locSelect) {
        judetSelect.innerHTML = '<option value="">Alege Judet...</option>';
        Object.keys(judeteLocalitati).forEach(j => {
            judetSelect.innerHTML += `<option value="${j}">${j}</option>`;
        });

        judetSelect.addEventListener('change', function() {
            const val = this.value;
            locSelect.innerHTML = '<option value="">Alege Localitate...</option>';
            if (val && judeteLocalitati[val]) {
                locSelect.disabled = false;
                judeteLocalitati[val].forEach(l => {
                    locSelect.innerHTML += `<option value="${l}">${l}</option>`;
                });
            } else {
                locSelect.disabled = true;
            }
        });
    }

    const formVarstaText = document.getElementById('varstaText');
    const formDate = document.getElementById('dataNasterii');
    
    if (formVarstaText && formDate) {
        formVarstaText.addEventListener('input', function() {
            const age = parseInt(this.value);
            if (!isNaN(age) && age > 0) {
                const currentYear = new Date().getFullYear();
                const targetYear = currentYear - age;
                
                formDate.max = `${targetYear}-12-31`;
                formDate.min = `${targetYear}-01-01`;
            }
        });
    }
}

function setupContactForm() {
    const cf = document.getElementById('contactForm');
    if(!cf) return;
    
    cf.addEventListener('submit', function(e) {
        e.preventDefault();
        let valid = true;
        
        cf.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
        
        const titlu = document.getElementById('msgTitle');
        const detalii = document.getElementById('msgDetails');
        
        if(titlu.value.trim().length < 3) {
            titlu.classList.add('error-border');
            valid = false;
        }
        
        if(detalii.value.trim().length < 10) {
            detalii.classList.add('error-border');
            valid = false;
        }

        if(valid) {
            alert('Mesaj trimis cu succes la echipa de suport!');
            cf.reset();
        } else {
            alert('Te rugăm să completezi câmpurile obligatorii corect.');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCarousel();
    renderClassicTable();
    renderVerticalTable();
    initCollapsibleLists();
    initFormDependencies();
    setupContactForm();
});
