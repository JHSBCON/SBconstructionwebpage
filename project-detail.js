const slug = new URLSearchParams(location.search).get('slug');
const projects = window.sbProjects;
const project = projects.find(item => item.slug === slug);

if (!project) {
  document.title = 'Project not found — SB Constructions';
  document.querySelector('#detail-cover').remove();
  document.querySelector('#detail-title').textContent = 'Project not found';
  document.querySelector('#detail-kicker').textContent = 'SB CONSTRUCTIONS';
  document.querySelector('.detail-main').innerHTML = '<div class="wrap"><p>This project is not available. <a href="projects.html">Explore all projects →</a></p></div>';
} else {
  // This page remains only as a fallback for existing project bookmarks.
  location.replace(`projects/${project.slug}.html`);
  document.title = `${project.name} — SB Constructions`;
  document.querySelector('meta[name="description"]').content = `${project.name}: ${project.type.toLowerCase()} in ${project.location}. Explore the project by SB Constructions.`;
  const cover = document.querySelector('#detail-cover');
  cover.src = `assets/${project.photos[0]}`;
  cover.alt = `${project.name} completed project`;
  document.querySelector('#detail-kicker').textContent = `${project.location} / ${project.type}`;
  document.querySelector('#detail-title').textContent = project.name;
  document.querySelector('#detail-heading').textContent = `Built for living.`;
  document.querySelector('#detail-type').textContent = project.type.toUpperCase();
  document.querySelector('#detail-description').textContent = project.description;
  for (const [index, id] of ['#detail-photo-one','#detail-photo-two'].entries()) {
    const photo = document.querySelector(id);
    photo.src = `assets/${project.photos[index + 1]}`;
    photo.alt = `${project.name} project photograph ${index + 2} of 3`;
  }
  const next = projects[(projects.indexOf(project) + 1) % projects.length];
  document.querySelector('#next-project').href = `project.html?slug=${next.slug}`;
  document.querySelector('#next-project').firstChild.textContent = `Next: ${next.name} `;
}
