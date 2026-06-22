(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const canvas = document.getElementById('bg-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let width, height, particles = [], animationId = null;

    const COUNT = 60;
    const CONN_DIST_SQ = 180 * 180;
    const MOUSE_DIST_SQ = 220 * 220;
    let mouse = { x: null, y: null };

    window.addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; }, { passive: true });
    window.addEventListener('mouseleave', () => { mouse.x = null; mouse.y = null; });

    function resize() { width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; }
    window.addEventListener('resize', resize);
    resize();

    class Particle {
        constructor() { this.reset(); }
        reset() {
            this.x  = Math.random() * width;
            this.y  = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;
            this.size = Math.random() * 2 + 1;
        }
        update() {
            this.x += this.vx;
            this.y += this.vy;
            if (this.x < 0 || this.x > width)  this.vx *= -1;
            if (this.y < 0 || this.y > height)  this.vy *= -1;
            if (mouse.x != null) {
                const dx = mouse.x - this.x, dy = mouse.y - this.y;
                const dSq = dx * dx + dy * dy;
                if (dSq < MOUSE_DIST_SQ) {
                    const d = Math.sqrt(dSq), f = (220 - d) / 220;
                    this.x -= (dx / d) * f * 3;
                    this.y -= (dy / d) * f * 3;
                }
            }
        }
        draw() {
            ctx.fillStyle = 'rgba(99, 102, 241, 0.55)';
            ctx.shadowColor = '#6366F1';
            ctx.shadowBlur = 6;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
            ctx.shadowBlur = 0;
        }
    }

    for (let i = 0; i < COUNT; i++) particles.push(new Particle());

    function animate() {
        ctx.clearRect(0, 0, width, height);
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dSq = dx * dx + dy * dy;
                if (dSq < CONN_DIST_SQ) {
                    const opacity = (1 - dSq / CONN_DIST_SQ) * 0.25;
                    ctx.strokeStyle = `rgba(99, 102, 241, ${opacity})`;
                    ctx.lineWidth = 0.8;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
        animationId = requestAnimationFrame(animate);
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { cancelAnimationFrame(animationId); animationId = null; }
        else if (!animationId) animate();
    });

    animate();
})();
