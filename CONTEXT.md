## Getting started

A new developer, in this order:

1. **Clone** the repository (a monorepo with submodules: `git clone --recurse-submodules`).
2. **Agent skills.** `npx skills experimental_install` restores the project's pinned set from `skills-lock.json`. Optional layers for your own work are installed once per user, outside the project: `npx @esoul-cz/skills install project helpers` (`npx @esoul-cz/skills layers` lists them).
3. **Local environment.** The `README.md` › Setup (or Requirements and Commands) of the repository: the Docker stack or `pnpm`, the `.env` file from `.env.example`.
4. **Access**, asked from the project lead or devops: the Freelo project (`## Freelo` below, once the project has it), the Jenkins job (its `Jenkins` check runs on every pull request) and the project's vault in 1Password (secrets and staging logins; never in Git, chat or tickets).
5. **Read** `AGENTS.md`, the index of `docs/project_guidelines/` and this file; then start with `esoul-workflow`.

## Commit convention

Commit messages are English and describe what the commit does (required). This project uses the recommended Gitmoji format below; a developer MAY use their own commit standard, and the agents of a developer who has none use this one. Use the `gitmoji-commit` skill for agent-authored commits in this format.

```text
:sparkles: [checkout] Add delivery selection

optional explanatory details
```

In the Gitmoji format use one shortcode per commit and split commits by intention. Context is optional. The message is imperative and starts with an uppercase letter. Keep the complete subject line at 72 characters or fewer when practical.

## Project profile

TBD

## Tracker

TBD

## Tasking convention

TBD

## Business time zone

TBD

## Locales

TBD

## Language per place

TBD

## Sources of truth

TBD
