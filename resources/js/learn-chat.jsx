import { createRoot } from 'react-dom/client';
import LearnChat from './components/LearnChat';

const root = document.getElementById('learn-chat-root');
if (root) {
    const data = JSON.parse(root.dataset.props);

    createRoot(root).render(
        <LearnChat
            token={data.token}
            csrfToken={data.csrfToken}
            initialMessages={data.initialMessages}
            curricula={data.curricula}
            defaultCurriculumId={data.defaultCurriculumId}
            defaultSkillLevel={data.defaultSkillLevel}
        />
    );
}
