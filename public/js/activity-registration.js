document.querySelectorAll('[data-group-registration]').forEach((form) => {
    const membersList = form.querySelector('[data-members-list]');
    const memberTemplate = form.querySelector('[data-member-template]');
    const addButton = form.querySelector('[data-add-member]');

    const updateMemberNumbers = () => {
        membersList.querySelectorAll('.activity-member').forEach((member, index) => {
            member.querySelector('[data-member-number]').textContent = String(index + 1);
            const removeButton = member.querySelector('[data-remove-member]');
            if (removeButton) {
                removeButton.hidden = membersList.children.length <= 2;
            }
        });
    };

    addButton.addEventListener('click', () => {
        const index = membersList.children.length;
        const member = memberTemplate.content.cloneNode(true);
        member.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace('__INDEX__', String(index));
        });
        membersList.append(member);
        updateMemberNumbers();
    });

    membersList.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-member]');
        if (!removeButton || membersList.children.length <= 2) {
            return;
        }

        removeButton.closest('.activity-member').remove();
        updateMemberNumbers();
    });

    updateMemberNumbers();
});
