<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Visualizador de Componentes"
        icon="ph ph-palette"
        badge="Design System UI"
        :breadcrumbs="$breadcrumbs">
    </x-page-header>

    <div class="flex flex-col gap-8 pb-12">

        <!-- 1. TIPOGRAFIA -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Tipografia (Inter)</h2>
            <div class="flex flex-col gap-4 text-gray-900 dark:text-white">
                <div><span class="t-display-48">Display 48</span></div>
                <div><span class="t-heading-xx-large">Heading XX Large (40px)</span></div>
                <div><span class="t-heading-x-large">Heading X Large (36px)</span></div>
                <div><span class="t-heading-large">Heading Large (32px)</span></div>
                <div><span class="t-heading-medium">Heading Medium (28px)</span></div>
                <div><span class="t-heading-small">Heading Small (24px)</span></div>
                <div><span class="t-heading-x-small">Heading X Small (20px)</span></div>
                <hr class="divider divider--horizontal my-2">
                <div><span class="t-body-18">Body 18 - Texto padrão de leitura para blocos de texto maiores.</span></div>
                <div><span class="t-body-16">Body 16 - Texto principal para a maioria das interfaces e parágrafos.</span></div>
                <div><span class="t-body-16-bold">Body 16 Bold - Destaque em parágrafos.</span></div>
                <div><span class="t-body-14">Body 14 - Texto secundário para legendas ou descrições curtas.</span></div>
            </div>
        </section>

        <!-- 2. BOTÕES (MATERIAL DESIGN + RIPPLE) -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Botões (Material Ripple)</h2>
            
            <div class="flex flex-wrap gap-8 items-end">
                <!-- Tamanhos -->
                <div class="flex flex-col gap-3">
                    <span class="t-label-14-semibold text-gray-500">Tamanhos (Primary)</span>
                    <div class="flex items-center gap-3">
                        <button class="btn btn--primary btn--small">Small Button</button>
                        <button class="btn btn--primary btn--medium">Medium Button</button>
                        <button class="btn btn--primary btn--large">Large Button</button>
                    </div>
                </div>

                <!-- Variantes -->
                <div class="flex flex-col gap-3">
                    <span class="t-label-14-semibold text-gray-500">Variantes de Estilo</span>
                    <div class="flex items-center gap-3">
                        <button class="btn btn--primary btn--medium"><i class="ph-bold ph-check"></i> Primary</button>
                        <button class="btn btn--cta btn--medium"><i class="ph-bold ph-star"></i> CTA (Call to Action)</button>
                        <button class="btn btn--secondary btn--medium"><i class="ph-bold ph-download-simple"></i> Secondary</button>
                        <button class="btn btn--medium" disabled>Disabled</button>
                    </div>
                </div>

                <!-- Dark Background Showcase -->
                <div class="flex flex-col gap-3 bg-gray-900 p-4 rounded-lg w-full max-w-sm">
                    <span class="t-label-14-semibold text-gray-400">Sobre fundo escuro (On Dark)</span>
                    <div class="flex items-center gap-3">
                        <button class="btn btn--ondark btn--medium">Botão On Dark</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. TAGS / BADGES -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Tags & Badges</h2>
            
            <div class="flex flex-col gap-6">
                <!-- Filled -->
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="t-label-14-semibold text-gray-500 w-full">Filled Tags (Medium)</span>
                    <span class="tag tag--medium tag--filled tag--purpura"><i class="ph-fill ph-crown"></i> Púrpura</span>
                    <span class="tag tag--medium tag--filled tag--ponkan">Ponkan</span>
                    <span class="tag tag--medium tag--filled tag--pitaya">Pitaya</span>
                    <span class="tag tag--medium tag--filled tag--pistache"><i class="ph-bold ph-check-circle"></i> Pistache</span>
                    <span class="tag tag--medium tag--filled tag--petunia">Petúnia</span>
                    <span class="tag tag--medium tag--filled tag--neutral">Neutral</span>
                </div>

                <!-- Outline -->
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="t-label-14-semibold text-gray-500 w-full">Outline Tags (Small)</span>
                    <span class="tag tag--small tag--outline tag--purpura">Púrpura Outline</span>
                    <span class="tag tag--small tag--outline tag--neutral">Neutral Outline</span>
                </div>
            </div>
        </section>

        <!-- 4. CARDS -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Cards Elevados</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card Simples -->
                <div class="card">
                    <div class="card__icon">
                        <i class="ph-fill ph-lightbulb"></i>
                    </div>
                    <h3 class="card__title">Material Card Standard</h3>
                    <p class="card__text">Este card utiliza sombras dinâmicas do Material Design. Ao passar o mouse, a elevação (sombra) aumenta, dando feedback visual nativo.</p>
                    <hr class="card__divider">
                    <div class="flex justify-end w-full">
                        <button class="btn btn--secondary btn--small w-full">Saber mais</button>
                    </div>
                </div>

                <!-- Curso Card -->
                <div class="curso-card lg:col-span-2 max-w-sm">
                    <div class="curso-card__media">
                        <!-- Imagem de Placeholder -->
                        <div class="curso-card__img bg-gradient-to-r from-purpura-400 to-petunia-500"></div>
                        <div class="curso-card__tags">
                            <span class="tag tag--small tag--filled tag--pitaya">LANÇAMENTO</span>
                        </div>
                    </div>
                    <div class="curso-card__body">
                        <h3 class="curso-card__title">Curso Design System Base</h3>
                        <ul class="curso-card__info">
                            <li class="curso-card__info-row"><i class="ph-bold ph-clock text-purpura-500"></i> 40 horas de conteúdo</li>
                            <li class="curso-card__info-row"><i class="ph-bold ph-users text-purpura-500"></i> 1.250 alunos inscritos</li>
                        </ul>
                    </div>
                    <div class="curso-card__footer">
                        <button class="btn btn--primary btn--medium w-full">Matricular agora</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. ACCORDION & TESTIMONIAL -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Accordion -->
            <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Accordion (Native Details)</h2>
                
                <div class="accordion">
                    <!-- O CSS foi feito para funcionar perfeitamente com a tag <details> nativa do HTML5 -->
                    <details class="accordion-item" open>
                        <summary class="accordion-item__summary">
                            <h4 class="accordion-item__title">O que é este componente?</h4>
                            <span class="accordion-item__icon">
                                <i class="ph-bold ph-caret-down"></i>
                            </span>
                        </summary>
                        <hr class="accordion-item__divider">
                        <div class="accordion-item__content">
                            <p class="accordion-item__text">É um accordion construído puramente com as tags nativas HTML <code>&lt;details&gt;</code> e <code>&lt;summary&gt;</code>, aproveitando a pseudo-classe <code>[open]</code> para animação e estilização sem precisar de JavaScript extra.</p>
                        </div>
                    </details>

                    <details class="accordion-item">
                        <summary class="accordion-item__summary">
                            <h4 class="accordion-item__title">Como adicionar o efeito Ripple?</h4>
                            <span class="accordion-item__icon">
                                <i class="ph-bold ph-caret-down"></i>
                            </span>
                        </summary>
                        <hr class="accordion-item__divider">
                        <div class="accordion-item__content">
                            <p class="accordion-item__text">O efeito ripple já está aplicado no CSS através do pseudo-elemento <code>::after</code> e do evento <code>:active</code> diretamente na classe summary.</p>
                        </div>
                    </details>
                </div>
            </section>

            <!-- Testimonial -->
            <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Testimonial</h2>
                
                <div class="flex items-center justify-center h-full pb-8">
                    <div class="testimonial">
                        <p class="testimonial__quote">"A nova interface ficou incrivelmente fluida. O uso do Material Design com a fonte Inter trouxe uma sobriedade profissional espetacular ao sistema administrativo."</p>
                        <div class="testimonial__author">
                            <div class="testimonial__avatar">RC</div>
                            <div class="testimonial__info">
                                <p class="testimonial__name">Raphael Cerqueira</p>
                                <p class="testimonial__role">Desenvolvedor Back-end</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <!-- 6. FORMULÁRIOS -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="t-heading-medium mb-6 border-b pb-2 text-gray-900 dark:text-white">Inputs (Formulários)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex flex-col gap-2">
                    <label class="t-label-14-semibold text-gray-700 dark:text-gray-300">Campo Texto Padrão</label>
                    <input type="text" placeholder="Digite seu nome">
                </div>

                <div class="flex flex-col gap-2">
                    <label class="t-label-14-semibold text-gray-700 dark:text-gray-300">Campo E-mail</label>
                    <input type="email" placeholder="email@dominio.com">
                </div>

                <div class="flex flex-col gap-2">
                    <label class="t-label-14-semibold text-gray-700 dark:text-gray-300">Select Padrão</label>
                    <select>
                        <option>Selecione uma opção</option>
                        <option>Opção 1</option>
                        <option>Opção 2</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 lg:col-span-3">
                    <label class="t-label-14-semibold text-gray-700 dark:text-gray-300">Área de Texto (Textarea)</label>
                    <textarea rows="3" placeholder="Digite uma observação..."></textarea>
                </div>

                <div class="flex items-center gap-3 lg:col-span-3 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-800">
                    <input type="checkbox" id="checkTerms">
                    <label for="checkTerms" class="t-body-14 text-gray-700 dark:text-gray-300 cursor-pointer select-none">Concordo com a padronização Material Design e o efeito Ripple CSS.</label>
                </div>
            </div>
        </section>
        
    </div>
</div>