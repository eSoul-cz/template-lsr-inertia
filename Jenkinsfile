@Library(['dockerHelpers', 'testing', 'deploy', 'pipelines']) _

// eSoul's shared `lsrPipeline` step (library `pipelines`), the LSR counterpart of `laravelPipeline`: checkout, the PHP checks (PHPStan,
// `composer cs`, tests, `composer audit`) and JS checks (ESLint, Prettier, `tsc`, Vitest, `pnpm audit`, build), a local build of the
// production image with the Compose smoke test on pull requests and, from the branches below, the multi-architecture image release.
// Stages, timeouts and credentials are described in `pipelines/vars/lsrPipeline.txt` of the eSoul-cz/jenkins-libraries repository.
// Everything not set here is the default of the step, which is what this template provides (Composer scripts `phpstan:ci`, `cs`,
// `test:parallel`; pnpm scripts `eslint`, `format:check`, `tsc`, `test`, `build`; `docker/Dockerfile` with a `production` target;
// `docker-compose.ci.yml` with the `http-smoke` service).
// `esoul-guidelines check` runs by itself (stage `Guidelines check`, from the project's own `vendor/`) because the project has an
// `esoul-guidelines.lock`: nothing to configure here, and no `extraChecks` entry for it.
//
// Adopting the template: set `image` to the release image name of the application (lowercase letters, digits, `_`, `.`, `-`; it is
// pushed to the `registry` namespace) and, to deploy after the release, add `stack: '<komodo stack>'` to the deploy rule. Without
// `stack` nothing is deployed.
lsrPipeline(
    image: 'replace-with-app-name',
    smoke: [composeFile: 'docker-compose.ci.yml', verificationServices: ['http-smoke']],

    // Release images are built and pushed from these branches.
    deploy: [[branches: ['main', 'master']]],

    // Addons add options of the pipeline here (a slot of the template; keep this line).
    // @esoul-slot pipeline-options
)
