import { useState } from 'react';

const SKILL_LEVELS = [
    { value: 'novice', label: 'Novice' },
    { value: 'intermediate', label: 'Intermediate' },
    { value: 'advanced', label: 'Advanced' },
    { value: 'expert', label: 'Expert' },
];

export default function SaveTopicPanel({ curricula, defaultCurriculumId, defaultSkillLevel, onSuggest, onSave }) {
    const [open, setOpen] = useState(false);
    const [suggesting, setSuggesting] = useState(false);
    const [saving, setSaving] = useState(false);
    const [result, setResult] = useState(null); // { ok: boolean, text: string }

    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [curriculumId, setCurriculumId] = useState(defaultCurriculumId ?? curricula[0]?.id ?? '');
    const [skillLevel, setSkillLevel] = useState(defaultSkillLevel ?? 'intermediate');

    async function handleSuggest() {
        setSuggesting(true);
        try {
            const suggestion = await onSuggest();
            if (suggestion) {
                setTitle(suggestion.title ?? '');
                setDescription(suggestion.description ?? '');
            }
        } finally {
            setSuggesting(false);
        }
    }

    async function handleSave() {
        if (!title.trim()) return;
        setSaving(true);
        setResult(null);
        try {
            const response = await onSave({ title: title.trim(), description: description.trim(), curriculumId, skillLevel });
            setResult(response);
            if (response.ok) {
                setTitle('');
                setDescription('');
            }
        } finally {
            setSaving(false);
        }
    }

    if (!open) {
        return (
            <div className="save-topic-section">
                <button className="save-topic-toggle" onClick={() => setOpen(true)}>
                    + Save a new topic to my curriculum
                </button>
            </div>
        );
    }

    return (
        <div className="save-topic-section">
            <button className="save-topic-toggle" onClick={() => setOpen(false)}>
                − Close
            </button>

            <p className="text-muted" style={{ fontSize: '.875rem', margin: '.75rem 0 .5rem' }}>
                Found something interesting? Save it as a topic to get future lessons about it.
            </p>

            <button
                className="btn btn-ghost btn-sm"
                style={{ marginBottom: '.75rem' }}
                disabled={suggesting}
                onClick={handleSuggest}
            >
                {suggesting ? 'Thinking…' : '✨ Suggest from this conversation'}
            </button>

            <div className="form-group">
                <label>Topic title</label>
                <input
                    type="text"
                    placeholder="e.g. Quantum entanglement"
                    maxLength={255}
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                />
            </div>

            <div className="form-group">
                <label>Description <span className="optional">(optional)</span></label>
                <textarea
                    rows={2}
                    placeholder="What specifically do you want to learn?"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                />
            </div>

            <div className="form-group">
                <label>Add to curriculum</label>
                <select value={curriculumId} onChange={(e) => setCurriculumId(e.target.value)}>
                    {curricula.map((c) => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                </select>
            </div>

            <div className="form-group">
                <label>Skill level</label>
                <div className="skill-level-selector">
                    {SKILL_LEVELS.map(({ value, label }) => (
                        <label key={value} className={`skill-option skill-${value} ${skillLevel === value ? 'selected' : ''}`}>
                            <input
                                type="radio"
                                name="save_skill_level"
                                value={value}
                                checked={skillLevel === value}
                                onChange={() => setSkillLevel(value)}
                            />
                            {label}
                        </label>
                    ))}
                </div>
            </div>

            <div style={{ display: 'flex', gap: '.5rem', marginTop: '.75rem' }}>
                <button className="btn btn-primary btn-sm" disabled={saving} onClick={handleSave}>
                    {saving ? 'Saving…' : 'Save topic'}
                </button>
                <button className="btn btn-ghost btn-sm" onClick={() => setOpen(false)}>Cancel</button>
            </div>

            {result && (
                <div className={result.ok ? 'sms-test-success' : 'sms-test-error'} style={{ marginTop: '.75rem' }}>
                    {result.text}
                </div>
            )}
        </div>
    );
}
