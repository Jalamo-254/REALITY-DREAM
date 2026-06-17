window.announcementPayload = {
    defaultAction: {
        href: 'enroll_redirect.html',
        text: 'View intake details'
    },
    items: [
        {
            href: '#',
            tag: 'Brochure',
            text: 'Download our latest brochure for courses, schedules, and contact details.',
            img: 'Assets/images/entre-1.jpg',
            alt: 'Brochure 1'
        },
        {
            href: '#',
            tag: 'Brochure',
            text: 'CCTV & Computer training — see course highlights and intake dates.',
            img: 'Assets/images/entre-2.jpg',
            alt: 'Brochure 2'
        },
        {
            href: '#',
            tag: 'Brochure',
            text: 'Limited seats: practical hands-on CCTV installer program.',
            img: 'Assets/images/enrol.jpg',
            alt: 'Brochure 3'
        },
        {
            href: 'enroll_redirect.html',
            tag: 'Intakes',
            text: 'Next dates: Jun 12 · Jul 10 · Aug 07',
            img: 'Assets/images/optimized/enrol.jpg',
            alt: 'Intakes thumbnail'
        }
        ,
        {
            href: '#',
            tag: 'Gallery',
            text: 'See student project highlights from our latest cohort.',
            img: 'Assets/images/optimized/student-5.jpg',
            alt: 'Student project'
        },
        {
            href: '#',
            tag: 'Workshop',
            text: 'Hands-on workshop this weekend — limited seats.',
            img: 'Assets/images/optimized/shop-1.jpeg',
            alt: 'Workshop thumbnail'
        }
    ]
};

function renderAnnouncementNav() {
    const nav = document.getElementById('announcementNav');
    const action = document.getElementById('announcementAction');
    if (!nav) return;

    const payload = window.announcementPayload || { items: [] };
    nav.classList.add('announcement-list');
    nav.innerHTML = '';

    payload.items.forEach((item) => {
        const link = document.createElement('a');
        link.className = 'announcement-item';
        link.href = item.href || '#';
        link.innerHTML = `
            <img class="announcement-image" src="${item.img}" alt="${item.alt || item.tag}" loading="lazy" decoding="async" />
            <div>
                <span class="announcement-tag">${item.tag}</span>
                <span>${item.text}</span>
            </div>
        `;
        nav.appendChild(link);
    });

    if (action && payload.defaultAction) {
        action.href = payload.defaultAction.href;
        action.innerHTML = `<i class="fas fa-calendar-check mr-2"></i>${payload.defaultAction.text}`;
    }
}

document.addEventListener('DOMContentLoaded', renderAnnouncementNav);
